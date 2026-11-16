<?php

namespace App\Actions;

use App\Models\Loan;
use Illuminate\Support\Facades\DB;

class CancelUnpaidLoanAction
{
    public function execute(Loan $loan, string $motivo = 'Reserva anulada por administración sin validación de pago.'): bool
    {
        if ($loan->estado === 'devuelto' || $loan->estaAnulado()) {
            return false;
        }

        return DB::transaction(function () use ($loan, $motivo) {
            /** @var Loan $locked */
            $locked = Loan::with('book')->lockForUpdate()->findOrFail($loan->id);

            if ($locked->estado === 'devuelto' || $locked->estaAnulado()) {
                return false;
            }

            $nota = trim(($locked->observaciones ? $locked->observaciones . ' | ' : '') . $motivo);

            $locked->update([
                'estado' => 'devuelto',
                'estado_pago' => 'anulado',
                'expira_pago_at' => null,
                'observaciones' => $nota,
            ]);

            $locked->book?->restaurarStockYNotificar(1, false);

            return true;
        });
    }
}
