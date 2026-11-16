<?php

namespace App\Actions;

use App\Models\Loan;
use Illuminate\Support\Facades\DB;

class ReturnLoanAction
{
    public int $lastKarmaAwarded = 0;

    public function execute(Loan $loan): bool
    {
        $this->lastKarmaAwarded = 0;

        if ($loan->estado === 'devuelto' || $loan->estaAnulado()) {
            return false;
        }

        DB::transaction(function () use ($loan) {
            $devueltoATiempo = $loan->fecha_devolucion_esperada !== null
                && now()->startOfDay()->lte($loan->fecha_devolucion_esperada);

            $updates = [
                'estado' => 'devuelto',
                'fecha_devolucion_real' => now()->toDateString(),
            ];

            // Si un préstamo pendiente se liquida en el acto de devolución en tienda
            if ($loan->estado_pago === 'pendiente') {
                $updates['estado_pago'] = 'pagado';
                $updates['fecha_pago'] = now();
                $updates['expira_pago_at'] = null;
            }

            $loan->update($updates);

            if ($devueltoATiempo && $loan->reader) {
                $loan->reader->registrarKarma(
                    25,
                    "Devolución puntual de «{$loan->book->titulo}»",
                    'loan',
                    $loan->id
                );
                $this->lastKarmaAwarded = 25;
            }

            $loan->book->restaurarStockYNotificar(1, false);
        });

        return true;
    }
}
