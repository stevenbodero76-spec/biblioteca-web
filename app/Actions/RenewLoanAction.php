<?php

namespace App\Actions;

use App\Models\Loan;
use App\Models\Waitlist;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RenewLoanAction
{
    public const DIAS_RENOVACION = 7;

    public const MAX_DIAS_TOTALES = 45;

    /**
     * Extiende el plazo de un préstamo activo si no existen lectores esperando en cola ni excede el tope máximo.
     */
    public function execute(Loan $loan): Loan
    {
        return DB::transaction(function () use ($loan) {
            /** @var Loan $locked */
            $locked = Loan::with('book')->lockForUpdate()->findOrFail($loan->id);

            if ($locked->estado === 'devuelto') {
                throw ValidationException::withMessages([
                    'loan' => 'No es posible renovar un préstamo que ya fue devuelto.',
                ]);
            }

            $hayCola = Waitlist::where('book_id', $locked->book_id)
                ->where('estado', 'en_espera')
                ->exists();

            if ($hayCola) {
                throw ValidationException::withMessages([
                    'loan' => 'Existen lectores en lista de espera para «'.($locked->book->titulo ?? 'esta obra').'»; no se puede renovar el préstamo.',
                ]);
            }

            $baseVencimiento = $locked->fecha_devolucion_esperada->gt(today())
                ? $locked->fecha_devolucion_esperada->copy()
                : today();

            $nuevaFecha = $baseVencimiento->addDays(self::DIAS_RENOVACION);
            $inicio = Carbon::parse($locked->fecha_prestamo)->startOfDay();

            if ($inicio->diffInDays($nuevaFecha) > self::MAX_DIAS_TOTALES) {
                throw ValidationException::withMessages([
                    'loan' => 'El préstamo alcanzó el límite máximo acumulado de '.self::MAX_DIAS_TOTALES.' días.',
                ]);
            }

            $notaRenovacion = 'Renovado +'.self::DIAS_RENOVACION.' días ('.now()->format('d/m/Y').').';
            $observaciones = trim(($locked->observaciones ? $locked->observaciones.' | ' : '').$notaRenovacion);

            $locked->update([
                'fecha_devolucion_esperada' => $nuevaFecha->toDateString(),
                'estado' => 'prestado',
                'observaciones' => $observaciones,
            ]);

            return $locked->fresh(['book.category', 'reader', 'user']);
        });
    }
}
