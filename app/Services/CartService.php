<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CartService
{
    private const SESSION_KEY = 'novaether_cart';

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function items(): Collection
    {
        $raw = session(self::SESSION_KEY, []);
        if (empty($raw) || ! is_array($raw)) {
            return collect();
        }

        $bookIds = collect($raw)->pluck('book_id')->unique()->values()->all();
        $books = Book::with('category')->whereIn('id', $bookIds)->get()->keyBy('id');

        return collect($raw)->map(function (array $entry, string $key) use ($books) {
            /** @var Book|null $book */
            $book = $books->get((int) ($entry['book_id'] ?? 0));
            if (! $book) {
                return null;
            }

            $tipo = ($entry['tipo_operacion'] ?? 'alquiler') === 'venta' ? 'venta' : 'alquiler';
            $cantidad = $tipo === 'venta' ? max(1, (int) ($entry['cantidad'] ?? 1)) : 1;
            $duracion = $tipo === 'alquiler' ? (int) ($entry['duracion_dias'] ?? 7) : null;
            $precio = $tipo === 'venta' ? (float) $book->precio_venta : (float) $book->precio_alquiler;

            return [
                'key' => $key,
                'book' => $book,
                'book_id' => $book->id,
                'tipo_operacion' => $tipo,
                'cantidad' => $cantidad,
                'duracion_dias' => $duracion,
                'precio_unitario' => $precio,
                'subtotal' => round($precio * $cantidad, 2),
            ];
        })->filter()->values();
    }

    public function add(Book $book, string $tipoOperacion, int $cantidad = 1, int $duracionDias = 7): void
    {
        $tipo = $tipoOperacion === 'venta' ? 'venta' : 'alquiler';
        $cart = session(self::SESSION_KEY, []);
        $key = "{$book->id}_{$tipo}";

        $enOtrasLineas = $this->cantidadLibroEnCarrito($cart, $book->id, $key);

        if ($tipo === 'venta') {
            $actual = (int) ($cart[$key]['cantidad'] ?? 0);
            $solicitado = max(1, $actual + $cantidad);

            if (($enOtrasLineas + $solicitado) > $book->stock_disponible) {
                $maxPosible = max(0, (int) $book->stock_disponible - $enOtrasLineas);
                throw ValidationException::withMessages([
                    'cantidad' => "Stock insuficiente para «{$book->titulo}» combinando compra y alquiler (disponible restante en carrito: {$maxPosible}).",
                ]);
            }

            $cart[$key] = [
                'book_id' => $book->id,
                'tipo_operacion' => 'venta',
                'cantidad' => $solicitado,
                'duracion_dias' => null,
            ];
        } else {
            if (($enOtrasLineas + 1) > $book->stock_disponible) {
                throw ValidationException::withMessages([
                    'cantidad' => "Ya reservaste todo el stock disponible de «{$book->titulo}» en tu carrito ({$book->stock_disponible} uds.).",
                ]);
            }

            $diasValidos = in_array($duracionDias, [7, 14, 30], true) ? $duracionDias : 7;
            $cart[$key] = [
                'book_id' => $book->id,
                'tipo_operacion' => 'alquiler',
                'cantidad' => 1,
                'duracion_dias' => $diasValidos,
            ];
        }

        session([self::SESSION_KEY => $cart]);
    }

    public function updateItem(string $key, int $cantidad, ?int $duracionDias = null): void
    {
        $cart = session(self::SESSION_KEY, []);
        if (! isset($cart[$key])) {
            return;
        }

        $book = Book::find($cart[$key]['book_id'] ?? 0);
        if (! $book) {
            unset($cart[$key]);
            session([self::SESSION_KEY => $cart]);

            return;
        }

        $enOtrasLineas = $this->cantidadLibroEnCarrito($cart, $book->id, $key);

        if (($cart[$key]['tipo_operacion'] ?? 'alquiler') === 'venta') {
            if ($cantidad <= 0) {
                unset($cart[$key]);
            } elseif (($enOtrasLineas + $cantidad) > $book->stock_disponible) {
                $maxPosible = max(0, (int) $book->stock_disponible - $enOtrasLineas);
                throw ValidationException::withMessages([
                    'cantidad' => "Stock insuficiente para «{$book->titulo}». Máximo permitido en compra manteniendo tus otras líneas: {$maxPosible}.",
                ]);
            } else {
                $cart[$key]['cantidad'] = $cantidad;
            }
        } else {
            $diasValidos = in_array((int) $duracionDias, [7, 14, 30], true) ? (int) $duracionDias : 7;
            $cart[$key]['duracion_dias'] = $diasValidos;
        }

        session([self::SESSION_KEY => $cart]);
    }

    public function remove(string $key): void
    {
        $cart = session(self::SESSION_KEY, []);
        unset($cart[$key]);
        session([self::SESSION_KEY => $cart]);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function count(): int
    {
        $raw = session(self::SESSION_KEY, []);
        if (! is_array($raw)) {
            return 0;
        }

        return collect($raw)->sum(fn ($item) => max(1, (int) ($item['cantidad'] ?? 1)));
    }

    public function subtotal(): float
    {
        return round((float) $this->items()->sum('subtotal'), 2);
    }

    public function soloAlquileres(): bool
    {
        $items = $this->items();

        return $items->isNotEmpty() && $items->every(fn ($i) => $i['tipo_operacion'] === 'alquiler');
    }

    private function cantidadLibroEnCarrito(array $cart, int $bookId, ?string $excludeKey = null): int
    {
        $total = 0;

        foreach ($cart as $k => $entry) {
            if ($k === $excludeKey) {
                continue;
            }
            if ((int) ($entry['book_id'] ?? 0) === $bookId) {
                $total += ($entry['tipo_operacion'] ?? 'alquiler') === 'venta'
                    ? max(1, (int) ($entry['cantidad'] ?? 1))
                    : 1;
            }
        }

        return $total;
    }
}
