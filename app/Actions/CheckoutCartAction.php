<?php

namespace App\Actions;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Reader;
use App\Models\User;
use App\Models\Waitlist;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutCartAction
{
    /**
     * @param  Collection<int, array<string, mixed>>  $items
     */
    public function execute(Collection $items, User $user, array $datos): Order
    {
        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Tu carrito está vacío. Agrega al menos una obra antes de finalizar.',
            ]);
        }

        return DB::transaction(function () use ($items, $user, $datos) {
            /** @var Reader|null $reader */
            $reader = $user->reader ? Reader::lockForUpdate()->find($user->reader->id) : null;

            if (! $reader || $reader->estado !== 'activo') {
                throw ValidationException::withMessages([
                    'reader' => 'Necesitas una ficha de cliente activa para completar compras o reservas.',
                ]);
            }

            $metodoPago = $datos['metodo_pago'] ?? 'yape_plin';
            $tieneVentas = $items->contains(fn ($i) => $i['tipo_operacion'] === 'venta');

            if ($metodoPago === 'membresia') {
                if ($tieneVentas) {
                    throw ValidationException::withMessages([
                        'metodo_pago' => 'La Membresía Club cubre únicamente alquileres. Para comprar tomos definitivos elige Yape/Plin, Tarjeta o Mostrador.',
                    ]);
                }
                if (! $user->esAdmin() && ! $reader->puedeUsarMembresia()) {
                    throw ValidationException::withMessages([
                        'metodo_pago' => 'Tu plan actual (Lector Free) no incluye alquileres en S/ 0.00. Elige otro medio de pago o mejora tu plan.',
                    ]);
                }
            }

            // Bloquear todos los libros en orden determinístico de ID para evitar deadlocks
            $bookIds = $items->pluck('book_id')->unique()->sort()->values()->all();
            $lockedBooks = Book::whereIn('id', $bookIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Verificar límite de alquileres simultáneos y stock disponible por obra
            $alquileresNuevos = $items->where('tipo_operacion', 'alquiler')->count();
            if ($alquileresNuevos > 0 && ($reader->prestamosActivosCount() + $alquileresNuevos) > CreateLoanAction::MAX_PRESTAMOS_ACTIVOS) {
                throw ValidationException::withMessages([
                    'cart' => 'Esta operación superaría tu límite de ' . CreateLoanAction::MAX_PRESTAMOS_ACTIVOS . ' préstamos/alquileres activos simultáneos.',
                ]);
            }

            $consumoPorLibro = [];
            $subtotal = 0.0;

            foreach ($items as $item) {
                /** @var Book|null $book */
                $book = $lockedBooks->get($item['book_id']);
                if (! $book) {
                    throw ValidationException::withMessages(['cart' => 'Una de las obras ya no existe en el catálogo.']);
                }

                $qty = $item['tipo_operacion'] === 'venta' ? (int) $item['cantidad'] : 1;
                $consumoPorLibro[$book->id] = ($consumoPorLibro[$book->id] ?? 0) + $qty;

                if ($book->stock_disponible < $consumoPorLibro[$book->id]) {
                    throw ValidationException::withMessages([
                        'cart' => "Stock insuficiente para «{$book->titulo}» (disponibles: {$book->stock_disponible}).",
                    ]);
                }

                $precioUnit = $metodoPago === 'membresia'
                    ? 0.0
                    : ($item['tipo_operacion'] === 'venta' ? (float) $book->precio_venta : (float) $book->precio_alquiler);

                $subtotal += round($precioUnit * $qty, 2);
            }

            $subtotal = round($subtotal, 2);

            // Cálculo seguro en servidor de canje de Puntos Nova (10 pts = S/ 1.00, tope 50% subtotal)
            $usarKarma = ! empty($datos['usar_karma']) && $metodoPago !== 'membresia' && $subtotal > 0;
            $puntosUsados = 0;
            $descuentoKarma = 0.0;

            if ($usarKarma && $reader->puntos_karma >= 10) {
                $descuentoMaxSoles = floor(($subtotal * 0.50));
                $bloquesPorSaldo = intdiv((int) $reader->puntos_karma, 10);
                $solesCanjeables = (int) min($descuentoMaxSoles, $bloquesPorSaldo);
                if ($solesCanjeables > 0) {
                    $puntosUsados = $solesCanjeables * 10;
                    $descuentoKarma = round((float) $solesCanjeables, 2);
                }
            }

            $total = round(max(0.0, $subtotal - $descuentoKarma), 2);

            $pagoConfirmado = in_array($metodoPago, ['membresia', 'tarjeta'], true) || $total === 0.0;
            $estadoPago = $pagoConfirmado ? 'pagado' : 'pendiente';
            $estadoPedido = $pagoConfirmado ? 'confirmado' : 'pendiente';

            $codigoOperacion = match ($metodoPago) {
                'tarjeta' => 'CARD-' . strtoupper(Str::random(8)),
                'yape_plin' => trim((string) ($datos['codigo_operacion'] ?? '')) ?: null,
                default => null,
            };

            $codigoOrden = 'ORD-' . date('Y') . '-' . strtoupper(Str::random(6));

            /** @var Order $order */
            $order = Order::create([
                'codigo_orden' => $codigoOrden,
                'user_id' => $user->id,
                'reader_id' => $reader->id,
                'metodo_pago' => $metodoPago,
                'estado_pago' => $estadoPago,
                'estado_pedido' => $estadoPedido,
                'subtotal' => $subtotal,
                'descuento_karma' => $descuentoKarma,
                'puntos_karma_usados' => $puntosUsados,
                'total' => $total,
                'codigo_operacion' => $codigoOperacion,
                'tipo_entrega' => ($datos['tipo_entrega'] ?? 'tienda') === 'delivery' ? 'delivery' : 'tienda',
                'direccion_entrega' => trim((string) ($datos['direccion_entrega'] ?? '')) ?: null,
                'fecha_pago' => $pagoConfirmado ? now() : null,
                'expira_pago_at' => $pagoConfirmado ? null : now()->addHours(24),
            ]);

            if ($puntosUsados > 0) {
                $reader->registrarKarma(
                    -$puntosUsados,
                    "Canje de descuento (-S/ " . number_format($descuentoKarma, 2) . ") en orden {$codigoOrden}",
                    'order',
                    $order->id
                );
            }

            foreach ($items as $item) {
                /** @var Book $book */
                $book = $lockedBooks->get($item['book_id']);
                $tipo = $item['tipo_operacion'];
                $qty = $tipo === 'venta' ? (int) $item['cantidad'] : 1;
                $precioUnit = $metodoPago === 'membresia'
                    ? 0.0
                    : ($tipo === 'venta' ? (float) $book->precio_venta : (float) $book->precio_alquiler);

                $loanId = null;

                if ($tipo === 'venta') {
                    $book->decrement('stock_disponible', $qty);
                    $book->decrement('stock_total', $qty);
                } else {
                    $dias = in_array((int) ($item['duracion_dias'] ?? 7), [7, 14, 30], true)
                        ? (int) $item['duracion_dias']
                        : 7;

                    $loanMetodo = $metodoPago === 'tarjeta' ? 'yape_plin' : $metodoPago;

                    $loan = Loan::create([
                        'book_id' => $book->id,
                        'reader_id' => $reader->id,
                        'user_id' => $user->id,
                        'fecha_prestamo' => now()->toDateString(),
                        'fecha_devolucion_esperada' => now()->addDays($dias)->toDateString(),
                        'estado' => 'prestado',
                        'metodo_pago' => $loanMetodo,
                        'monto' => $precioUnit,
                        'estado_pago' => $estadoPago,
                        'codigo_operacion' => $codigoOperacion,
                        'fecha_pago' => $pagoConfirmado ? now() : null,
                        'expira_pago_at' => $pagoConfirmado ? null : now()->addHours(24),
                        'observaciones' => "Generado desde Orden {$codigoOrden}.",
                    ]);

                    $book->decrement('stock_disponible');

                    Waitlist::where('book_id', $book->id)
                        ->where('reader_id', $reader->id)
                        ->whereIn('estado', ['en_espera', 'notificado'])
                        ->update(['estado' => 'completado']);

                    $loanId = $loan->id;
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'book_id' => $book->id,
                    'tipo_operacion' => $tipo,
                    'cantidad' => $qty,
                    'duracion_dias' => $tipo === 'alquiler' ? (int) ($item['duracion_dias'] ?? 7) : null,
                    'precio_unitario' => $precioUnit,
                    'subtotal' => round($precioUnit * $qty, 2),
                    'loan_id' => $loanId,
                ]);
            }

            return $order;
        });
    }
}
