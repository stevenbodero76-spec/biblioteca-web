<?php

namespace App\Actions;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Reader;
use App\Models\User;
use App\Models\Waitlist;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateLoanAction
{
    public const MAX_PRESTAMOS_ACTIVOS = 3;

    public function execute(array $datos, User $actor): Loan
    {
        return DB::transaction(function () use ($datos, $actor) {
            /** @var Book $book */
            $book = Book::lockForUpdate()->findOrFail($datos['book_id']);

            /** @var Reader $reader */
            $reader = Reader::lockForUpdate()->findOrFail($datos['reader_id']);

            if ($book->stock_disponible <= 0) {
                throw ValidationException::withMessages([
                    'book_id' => "PRÉSTAMO RECHAZADO: El libro «{$book->titulo}» NO tiene ejemplares disponibles actualmente (Disponibilidad: 0 de {$book->stock_total}).",
                ]);
            }

            if ($reader->estado !== 'activo') {
                throw ValidationException::withMessages([
                    'reader_id' => "PRÉSTAMO RECHAZADO: El cliente «{$reader->nombre_completo}» se encuentra SUSPENDIDO.",
                ]);
            }

            if ($reader->prestamosActivosCount() >= self::MAX_PRESTAMOS_ACTIVOS) {
                throw ValidationException::withMessages([
                    'reader_id' => "PRÉSTAMO RECHAZADO: «{$reader->nombre_completo}» ya alcanzó el límite de ".self::MAX_PRESTAMOS_ACTIVOS.' préstamos activos simultáneos.',
                ]);
            }

            $loan = Loan::create([
                'book_id' => $book->id,
                'reader_id' => $reader->id,
                'user_id' => $actor->id,
                'fecha_prestamo' => $datos['fecha_prestamo'],
                'fecha_devolucion_esperada' => $datos['fecha_devolucion_esperada'],
                'estado' => 'prestado',
                'observaciones' => filled($datos['observaciones'] ?? null)
                    ? trim((string) $datos['observaciones'])
                    : 'Préstamo público gratuito.',
            ]);

            $book->decrement('stock_disponible');

            Waitlist::where('book_id', $book->id)
                ->where('reader_id', $reader->id)
                ->whereIn('estado', ['en_espera', 'notificado'])
                ->update(['estado' => 'completado']);

            Book::limpiarCacheCatalogo();

            return $loan;
        });
    }
}
