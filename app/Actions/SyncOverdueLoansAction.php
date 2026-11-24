<?php

namespace App\Actions;

use App\Models\Loan;

class SyncOverdueLoansAction
{
    public function execute(): int
    {
        return Loan::where('estado', 'prestado')
            ->whereDate('fecha_devolucion_esperada', '<', now()->toDateString())
            ->update(['estado' => 'vencido']);
    }
}
