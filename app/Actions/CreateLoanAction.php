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
                    'reader_id' => "PRÉSTAMO RECHAZADO: «{$reader->nombre_completo}» ya alcanzó el límite de " . self::MAX_PRESTAMOS_ACTIVOS . ' préstamos activos simultáneos.',
                ]);
            }

            $metodoSolicitado = $datos['metodo_pago'] ?? 'gratuito';

            if ($metodoSolicitado === 'membresia' && ! $actor->esAdmin() && ! $reader->puedeUsarMembresia()) {
                throw ValidationException::withMessages([
                    'metodo_pago' => 'Tu plan actual (Lector Free) no incluye reservas con S/ 0.00. Selecciona préstamo público gratuito o actualiza tu ficha.',
                ]);
            }

            $esGratuitoOMembresia = in_array($metodoSolicitado, ['gratuito', 'membresia'], true);
            $metodoPago = $esGratuitoOMembresia ? 'membresia' : $metodoSolicitado;
            $monto = $esGratuitoOMembresia ? 0.00 : (float) $book->precio_alquiler;
            $codigoOperacion = trim((string) ($datos['codigo_operacion'] ?? '')) ?: null;

            $pagoInmediato = $esGratuitoOMembresia
                || ($actor->esAdmin() && ! empty($datos['pago_verificado_admin']));

            $estadoPago = $pagoInmediato ? 'pagado' : 'pendiente';
            $fechaPago = $pagoInmediato ? now() : null;
            $expiraPago = $pagoInmediato ? null : now()->addHours(24);

            $loan = Loan::create([
                'book_id' => $book->id,
                'reader_id' => $reader->id,
                'user_id' => $actor->id,
                'fecha_prestamo' => $datos['fecha_prestamo'],
                'fecha_devolucion_esperada' => $datos['fecha_devolucion_esperada'],
                'estado' => 'prestado',
                'metodo_pago' => $metodoPago,
                'monto' => $monto,
                'estado_pago' => $estadoPago,
                'codigo_operacion' => $codigoOperacion,
                'fecha_pago' => $fechaPago,
                'expira_pago_at' => $expiraPago,
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            $book->decrement('stock_disponible');

            Waitlist::where('book_id', $book->id)
                ->where('reader_id', $reader->id)
                ->whereIn('estado', ['en_espera', 'notificado'])
                ->update(['estado' => 'completado']);

            return $loan;
        });
    }
}
