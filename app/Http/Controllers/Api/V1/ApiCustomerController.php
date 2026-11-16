<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CheckoutCartAction;
use App\Actions\CreateLoanAction;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Favorite;
use App\Models\Loan;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiCustomerController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('reader');
        $reader = $user->reader;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'rol' => $user->rol,
            ],
            'reader' => $reader ? [
                'id' => $reader->id,
                'dni_codigo' => $reader->dni_codigo,
                'telefono' => $reader->telefono,
                'plan_suscripcion' => $reader->plan_suscripcion,
                'etiqueta_plan' => $reader->etiquetaPlan(),
                'plan_vence_at' => $reader->plan_vence_at?->toDateString(),
                'puntos_karma' => $reader->puntos_karma,
                'nivel_karma' => $reader->nivelKarma(),
                'estado' => $reader->estado,
            ] : null,
            'resumen' => [
                'prestamos_activos' => $reader?->prestamosActivosCount() ?? 0,
                'favoritos' => $user->favorites()->count(),
                'ordenes' => $user->orders()->count(),
            ],
        ]);
    }

    public function favorites(Request $request): JsonResponse
    {
        $books = $request->user()->favoriteBooks()->with('category')->get();

        return response()->json(['data' => $books]);
    }

    public function toggleFavorite(Request $request, Book $book): JsonResponse
    {
        $userId = $request->user()->id;
        $existente = Favorite::where('user_id', $userId)->where('book_id', $book->id)->first();

        if ($existente) {
            $existente->delete();
            $favorito = false;
        } else {
            Favorite::create(['user_id' => $userId, 'book_id' => $book->id]);
            $favorito = true;
        }

        return response()->json(['favorito' => $favorito, 'book_id' => $book->id]);
    }

    public function loans(Request $request): JsonResponse
    {
        $loans = Loan::with('book.category')
            ->visiblePara($request->user())
            ->latest()
            ->paginate(15);

        $loans->getCollection()->transform(function (Loan $loan) {
            $loan->setAttribute('codigo_ticket', $loan->codigoTicket());

            return $loan;
        });

        return response()->json($loans);
    }

    public function storeLoan(Request $request, CreateLoanAction $action): JsonResponse
    {
        $datos = $request->validate([
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'duracion_dias' => ['required', 'integer', 'in:7,14,30'],
            'metodo_pago' => ['required', 'in:membresia,yape_plin,mostrador'],
            'codigo_operacion' => ['nullable', 'string', 'max:60'],
        ]);

        $user = $request->user();
        if (! $user->reader) {
            return response()->json(['message' => 'Tu cuenta no posee ficha de cliente activa.'], 422);
        }

        $loan = $action->execute([
            'book_id' => $datos['book_id'],
            'reader_id' => $user->reader->id,
            'fecha_prestamo' => now()->toDateString(),
            'fecha_devolucion_esperada' => now()->addDays((int) $datos['duracion_dias'])->toDateString(),
            'metodo_pago' => $datos['metodo_pago'],
            'codigo_operacion' => $datos['codigo_operacion'] ?? null,
        ], $user);

        return response()->json([
            'message' => 'Reserva generada correctamente.',
            'codigo_ticket' => $loan->codigoTicket(),
            'data' => $loan->load('book'),
        ], 201);
    }

    public function orders(Request $request): JsonResponse
    {
        $orders = Order::with('items.book')
            ->visiblePara($request->user())
            ->latest()
            ->paginate(15);

        return response()->json($orders);
    }

    public function storeOrder(Request $request, CheckoutCartAction $action): JsonResponse
    {
        $datos = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.book_id' => ['required', 'integer', 'exists:books,id'],
            'items.*.tipo_operacion' => ['required', 'in:venta,alquiler'],
            'items.*.cantidad' => ['nullable', 'integer', 'min:1', 'max:20'],
            'items.*.duracion_dias' => ['nullable', 'integer', 'in:7,14,30'],
            'metodo_pago' => ['required', 'in:yape_plin,tarjeta,mostrador,membresia'],
            'codigo_operacion' => ['nullable', 'string', 'max:60'],
            'tipo_entrega' => ['required', 'in:tienda,delivery'],
            'direccion_entrega' => ['nullable', 'required_if:tipo_entrega,delivery', 'string', 'max:255'],
            'usar_karma' => ['nullable', 'boolean'],
        ]);

        $bookIds = collect($datos['items'])->pluck('book_id')->unique()->all();
        $books = Book::whereIn('id', $bookIds)->get()->keyBy('id');

        $cartItems = collect($datos['items'])->map(function (array $entry) use ($books) {
            $book = $books->get((int) $entry['book_id']);
            $tipo = $entry['tipo_operacion'];
            $qty = $tipo === 'venta' ? max(1, (int) ($entry['cantidad'] ?? 1)) : 1;

            return [
                'book_id' => $book->id,
                'book' => $book,
                'tipo_operacion' => $tipo,
                'cantidad' => $qty,
                'duracion_dias' => $tipo === 'alquiler' ? (int) ($entry['duracion_dias'] ?? 7) : null,
            ];
        });

        $order = $action->execute($cartItems, $request->user(), $datos);

        return response()->json([
            'message' => 'Orden registrada correctamente.',
            'data' => $order->load('items.book', 'items.loan'),
        ], 201);
    }
}
