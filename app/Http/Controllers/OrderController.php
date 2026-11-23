<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index(): RedirectResponse
    {
        Gate::authorize('viewAny', Order::class);

        return redirect()->route('books.index');
    }

    public function show(Order $order): RedirectResponse
    {
        Gate::authorize('view', $order);

        return redirect()->route('books.index');
    }

    public function confirmPayment(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('manage', Order::class);

        $datos = $request->validate([
            'codigo_operacion' => ['nullable', 'string', 'max:60'],
        ]);

        if ($order->estado_pago !== 'pendiente') {
            return back()->with('error', 'Esta orden ya no tiene pago pendiente.');
        }

        DB::transaction(function () use ($order, $datos) {
            /** @var Order $locked */
            $locked = Order::with('items.loan')->lockForUpdate()->findOrFail($order->id);
            if ($locked->estado_pago !== 'pendiente') {
                return;
            }

            $codigo = trim((string) ($datos['codigo_operacion'] ?? '')) ?: $locked->codigo_operacion;

            $locked->update([
                'estado_pago' => 'pagado',
                'estado_pedido' => $locked->estado_pedido === 'pendiente' ? 'confirmado' : $locked->estado_pedido,
                'codigo_operacion' => $codigo,
                'fecha_pago' => now(),
                'expira_pago_at' => null,
            ]);

            foreach ($locked->items as $item) {
                if ($item->loan && $item->loan->estado_pago === 'pendiente') {
                    $item->loan->update([
                        'estado_pago' => 'pagado',
                        'codigo_operacion' => $codigo,
                        'fecha_pago' => now(),
                        'expira_pago_at' => null,
                    ]);
                }
            }
        });

        return back()->with('success', "Pago de orden {$order->codigo_orden} verificado y confirmado.");
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('manage', Order::class);

        $datos = $request->validate([
            'estado_pedido' => ['required', 'in:pendiente,confirmado,entregado'],
        ]);

        $order->update(['estado_pedido' => $datos['estado_pedido']]);

        return back()->with('success', "Estado logístico de {$order->codigo_orden} actualizado.");
    }

    public function cancel(Order $order): RedirectResponse
    {
        Gate::authorize('manage', Order::class);

        if ($order->estado_pedido === 'cancelado' || $order->estado_pago === 'anulado') {
            return back()->with('error', 'La orden ya se encuentra anulada.');
        }

        DB::transaction(function () use ($order) {
            /** @var Order $locked */
            $locked = Order::with(['items.book', 'items.loan', 'reader'])->lockForUpdate()->findOrFail($order->id);

            $locked->update([
                'estado_pago' => 'anulado',
                'estado_pedido' => 'cancelado',
                'expira_pago_at' => null,
            ]);

            foreach ($locked->items as $item) {
                if ($item->tipo_operacion === 'venta' && $item->book) {
                    $item->book->restaurarStockYNotificar((int) $item->cantidad, true);
                } elseif ($item->loan && $item->loan->estado !== 'devuelto' && ! $item->loan->estaAnulado()) {
                    $item->loan->update([
                        'estado' => 'devuelto',
                        'estado_pago' => 'anulado',
                        'expira_pago_at' => null,
                    ]);
                    $item->book?->restaurarStockYNotificar(1, false);
                }
            }

            if ($locked->puntos_karma_usados > 0 && $locked->reader) {
                $locked->reader->registrarKarma(
                    (int) $locked->puntos_karma_usados,
                    "Reembolso de Puntos Nova por anulación de orden {$locked->codigo_orden}",
                    'order',
                    $locked->id
                );
            }
        });

        return back()->with('success', "Orden {$order->codigo_orden} anulada y stock/puntos restablecidos.");
    }
}
