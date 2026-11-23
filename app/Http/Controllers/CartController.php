<?php

namespace App\Http\Controllers;

use App\Actions\CheckoutCartAction;
use App\Models\Book;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cart) {}

    public function index(): RedirectResponse
    {
        return redirect()->route('books.index');
    }

    public function add(Request $request, Book $book): RedirectResponse|JsonResponse
    {
        $datos = $request->validate([
            'tipo_operacion' => ['nullable', 'in:venta,alquiler'],
            'cantidad' => ['nullable', 'integer', 'min:1', 'max:20'],
            'duracion_dias' => ['nullable', 'integer', 'in:7,14,30'],
        ]);

        if (! $book->estaDisponible()) {
            $msg = "«{$book->titulo}» está agotado en este momento. Puedes anotarte en la lista de espera.";
            if ($request->expectsJson()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $tipo = $datos['tipo_operacion'] ?? 'venta';
        $cantidad = (int) ($datos['cantidad'] ?? 1);
        $duracion = (int) ($datos['duracion_dias'] ?? 7);

        $this->cart->add($book, $tipo, $cantidad, $duracion);

        $etiqueta = $tipo === 'venta' ? 'Compra directa' : "Alquiler ({$duracion} días)";
        $msg = "🛒 «{$book->titulo}» ({$etiqueta}) añadido a tu carrito.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'message' => $msg,
                'cart_count' => $this->cart->count(),
            ]);
        }

        return back()->with('success', $msg);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $datos = $request->validate([
            'cantidad' => ['required', 'integer', 'min:0', 'max:50'],
            'duracion_dias' => ['nullable', 'integer', 'in:7,14,30'],
        ]);

        $this->cart->updateItem($key, (int) $datos['cantidad'], $datos['duracion_dias'] ?? null);

        return back()->with('success', 'Carrito actualizado.');
    }

    public function remove(string $key): RedirectResponse
    {
        $this->cart->remove($key);

        return back()->with('success', 'Obra retirada del carrito.');
    }

    public function checkout(Request $request, CheckoutCartAction $action): RedirectResponse
    {
        $rules = [
            'metodo_pago' => ['required', 'in:yape_plin,tarjeta,mostrador,membresia'],
            'codigo_operacion' => ['nullable', 'string', 'max:60'],
            'tipo_entrega' => ['required', 'in:tienda,delivery'],
            'direccion_entrega' => ['nullable', 'required_if:tipo_entrega,delivery', 'string', 'max:255'],
            'usar_karma' => ['nullable', 'boolean'],
        ];

        if ($request->input('metodo_pago') === 'tarjeta') {
            $rules['card_number'] = ['required', 'string', 'min:12', 'max:24'];
            $rules['card_expiry'] = ['required', 'string', 'min:4', 'max:7'];
            $rules['card_cvv'] = ['required', 'string', 'min:3', 'max:4'];
        }

        $datos = $request->validate($rules, [
            'direccion_entrega.required_if' => 'Ingresa la dirección de envío en Lima / Perú para modalidad delivery.',
        ]);

        if ($datos['metodo_pago'] === 'tarjeta') {
            $digits = preg_replace('/\D/', '', (string) $request->input('card_number', ''));
            if (strlen($digits) < 12) {
                throw ValidationException::withMessages([
                    'card_number' => 'Número de tarjeta inválido en pasarela sandbox.',
                ]);
            }
        }

        $items = $this->cart->items();
        $order = $action->execute($items, $request->user(), $datos);
        $this->cart->clear();

        $msg = $order->estado_pago === 'pagado'
            ? "Orden {$order->codigo_orden} confirmada y pagada correctamente."
            : "Orden {$order->codigo_orden} registrada. Completa y valida tu pago dentro de las próximas 24 horas.";

        return redirect()
            ->route('orders.show', $order)
            ->with('success', $msg);
    }
}
