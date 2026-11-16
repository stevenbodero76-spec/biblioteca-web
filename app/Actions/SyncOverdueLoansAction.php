<?php

namespace App\Actions;

use App\Models\Loan;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class SyncOverdueLoansAction
{
    public int $lastExpiredReleased = 0;

    public function execute(): int
    {
        $this->lastExpiredReleased = 0;

        // 1. Liberar préstamos pendientes cuyo plazo de pago/recojo (24h) expiró
        $expiredLoans = Loan::with('book')
            ->where('estado', 'prestado')
            ->where('estado_pago', 'pendiente')
            ->whereNotNull('expira_pago_at')
            ->where('expira_pago_at', '<', now())
            ->get();

        foreach ($expiredLoans as $loan) {
            DB::transaction(function () use ($loan) {
                $fresh = Loan::lockForUpdate()->find($loan->id);
                if (! $fresh || $fresh->estado !== 'prestado' || $fresh->estado_pago !== 'pendiente') {
                    return;
                }

                $nota = trim(($fresh->observaciones ? $fresh->observaciones . ' | ' : '') . 'Reserva expirada automáticamente tras 24h sin validación de pago.');
                $fresh->update([
                    'estado' => 'devuelto',
                    'estado_pago' => 'anulado',
                    'observaciones' => $nota,
                ]);

                $fresh->book?->restaurarStockYNotificar(1, false);
                $this->lastExpiredReleased++;
            });
        }

        // 2. Liberar órdenes de compra/carrito impagas tras expiración
        $expiredOrders = Order::with(['items.book', 'reader'])
            ->where('estado_pago', 'pendiente')
            ->whereNotNull('expira_pago_at')
            ->where('expira_pago_at', '<', now())
            ->get();

        foreach ($expiredOrders as $order) {
            DB::transaction(function () use ($order) {
                $freshOrder = Order::with(['items.book', 'reader'])->lockForUpdate()->find($order->id);
                if (! $freshOrder || $freshOrder->estado_pago !== 'pendiente') {
                    return;
                }

                $freshOrder->update([
                    'estado_pago' => 'anulado',
                    'estado_pedido' => 'cancelado',
                ]);

                foreach ($freshOrder->items as $item) {
                    if ($item->tipo_operacion === 'venta' && $item->book) {
                        $item->book->restaurarStockYNotificar((int) $item->cantidad, true);
                    }
                }

                if ($freshOrder->puntos_karma_usados > 0 && $freshOrder->reader) {
                    $freshOrder->reader->registrarKarma(
                        (int) $freshOrder->puntos_karma_usados,
                        "Reembolso de Puntos Nova por expiración de orden {$freshOrder->codigo_orden}",
                        'order',
                        $freshOrder->id
                    );
                }

                $this->lastExpiredReleased++;
            });
        }

        // 3. Sincronizar préstamos activos vencidos
        return Loan::where('estado', 'prestado')
            ->where('estado_pago', '!=', 'anulado')
            ->whereDate('fecha_devolucion_esperada', '<', now()->toDateString())
            ->update(['estado' => 'vencido']);
    }
}
