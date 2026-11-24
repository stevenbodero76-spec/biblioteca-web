<?php

namespace App\Actions;

use App\Models\Book;
use App\Models\KarmaMovement;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReturnLoanAction
{
    public int $lastKarmaAwarded = 0;

    public function execute(Loan $loan, ?User $actor = null): bool
    {
        $this->lastKarmaAwarded = 0;

        if ($loan->estado === 'devuelto') {
            return false;
        }

        DB::transaction(function () use ($loan, $actor) {
            /** @var Loan $locked */
            $locked = Loan::with(['book', 'reader'])->lockForUpdate()->findOrFail($loan->id);

            if ($locked->estado === 'devuelto') {
                return;
            }

            $devueltoATiempo = $locked->fecha_devolucion_esperada !== null
                && now()->startOfDay()->lte($locked->fecha_devolucion_esperada);

            $locked->update([
                'estado' => 'devuelto',
                'fecha_devolucion_real' => now()->toDateString(),
            ]);

            // Evita abuso de Karma (auto-prestar y devolver en el mismo día por cuenta cliente)
            $elegibleAutodevolucion = $actor === null
                || $actor->esAdmin()
                || ($locked->fecha_prestamo !== null && $locked->fecha_prestamo->lt(today()));

            if ($devueltoATiempo && $elegibleAutodevolucion && $locked->reader) {
                $yaOtorgado = KarmaMovement::where('reader_id', $locked->reader->id)
                    ->where('referencia_tipo', 'loan')
                    ->where('referencia_id', $locked->id)
                    ->exists();

                if (! $yaOtorgado) {
                    $locked->reader->registrarKarma(
                        25,
                        "Devolución puntual de «{$locked->book->titulo}»",
                        'loan',
                        $locked->id
                    );
                    $this->lastKarmaAwarded = 25;
                }
            }

            $locked->book->restaurarStockYNotificar(1, false);
        });

        Book::limpiarCacheCatalogo();

        return true;
    }
}
