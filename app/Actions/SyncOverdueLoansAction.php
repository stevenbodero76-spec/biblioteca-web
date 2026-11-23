<?php

namespace App\Actions;

use App\Models\Loan;
use Illuminate\Support\Facades\DB;

class SyncOverdueLoansAction
{
    public int $lastExpiredReleased = 0;

    public function execute(): int
    {
        $this->lastExpiredReleased = 0;

        // 1. Liberar préstamos pendientes cuyo plazo de recojo (24h) expiró
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

                $nota = trim(($fresh->observaciones ? $fresh->observaciones . ' | ' : '') . 'Reserva expirada automáticamente tras 24h.');
                $fresh->update([
                    'estado' => 'devuelto',
                    'estado_pago' => 'anulado',
                    'observaciones' => $nota,
                ]);

                $fresh->book?->restaurarStockYNotificar(1, false);
                $this->lastExpiredReleased++;
            });
        }

        // 2. Sincronizar préstamos activos vencidos
        return Loan::where('estado', 'prestado')
            ->where('estado_pago', '!=', 'anulado')
            ->whereDate('fecha_devolucion_esperada', '<', now()->toDateString())
            ->update(['estado' => 'vencido']);
    }
}
