<?php

namespace App\Actions;

use App\Models\Loan;
use Illuminate\Support\Facades\DB;

class ConfirmLoanPaymentAction
{
    public function execute(Loan $loan, ?string $codigoOperacion = null): bool
    {
        if ($loan->estado_pago !== 'pendiente') {
            return false;
        }

        return DB::transaction(function () use ($loan, $codigoOperacion) {
            /** @var Loan $locked */
            $locked = Loan::lockForUpdate()->findOrFail($loan->id);
            if ($locked->estado_pago !== 'pendiente') {
                return false;
            }

            $locked->update([
                'estado_pago' => 'pagado',
                'codigo_operacion' => trim((string) ($codigoOperacion ?: $locked->codigo_operacion)) ?: null,
                'fecha_pago' => now(),
                'expira_pago_at' => null,
            ]);

            return true;
        });
    }
}
