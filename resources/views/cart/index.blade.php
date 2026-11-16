@extends('layouts.app')

@section('title', 'Mi Carrito · NOVAETHER™')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-white">🛒 Mi Carrito de Compras y Reservas</h1>
        <p class="text-sm text-slate-300">Combina compras definitivas de libros/manga y alquileres con Ticket QR en una sola orden.</p>
    </div>
    <a href="{{ route('books.index') }}" class="text-xs font-extrabold uppercase text-amber-400 hover:underline">← Seguir explorando</a>
</div>

@if($items->isEmpty())
    <div class="bg-slate-900/95 rounded-3xl border border-slate-800 p-12 text-center space-y-3 shadow-2xl">
        <div class="text-5xl">🛒</div>
        <h2 class="text-lg font-extrabold text-white">Tu carrito está vacío por ahora</h2>
        <p class="text-sm text-slate-300 max-w-md mx-auto">Agrega cualquier libro o manga para compra definitiva o alquiler desde el catálogo.</p>
        <a href="{{ route('books.index') }}" class="inline-block bg-gradient-to-r from-amber-400 to-orange-500 text-slate-950 text-xs font-black px-6 py-3 rounded-xl shadow">
            Explorar Catálogo →
        </a>
    </div>
@else
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Lista de Items -->
        <div class="lg:col-span-7 space-y-3">
            @foreach($items as $item)
                @php $book = $item['book']; @endphp
                <div class="bg-slate-900/95 rounded-2xl border border-slate-800 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-lg">
                    <div class="flex items-center gap-3.5 min-w-0">
                        <div class="w-16 h-24 rounded-xl overflow-hidden bg-slate-950 border border-slate-700 flex-shrink-0">
                            @if($book->portada_url)
                                <img src="{{ $book->portada_url }}" alt="{{ $book->titulo }}" class="w-full h-full object-cover">
                            @endif
                        </div>
                        <div class="min-w-0">
                            <span class="inline-block text-[10px] font-black uppercase px-2 py-0.5 rounded {{ $item['tipo_operacion'] === 'venta' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-400/20 text-amber-300' }}">
                                {{ $item['tipo_operacion'] === 'venta' ? 'Compra Directa' : 'Alquiler QR' }}
                            </span>
                            <a href="{{ route('books.show', $book) }}" class="block font-bold text-white hover:text-amber-300 truncate mt-1">
                                {{ $book->titulo }}
                            </a>
                            <p class="text-xs text-slate-400">{{ $book->autor }} · Disp: {{ $book->stock_disponible }}</p>
                            <p class="text-sm font-black text-amber-400 mt-1">S/ {{ number_format($item['subtotal'], 2) }}</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 self-end sm:self-center">
                        <form action="{{ route('cart.update', $item['key']) }}" method="POST" class="flex items-center gap-1.5">
                            @csrf
                            @method('PATCH')
                            @if($item['tipo_operacion'] === 'venta')
                                <input type="number" name="cantidad" value="{{ $item['cantidad'] }}" min="1" max="{{ max(1, $book->stock_disponible) }}"
                                       class="w-16 rounded-xl bg-slate-950 border border-slate-700 px-2.5 py-1.5 text-xs text-white text-center">
                            @else
                                <input type="hidden" name="cantidad" value="1">
                                <select name="duracion_dias" class="rounded-xl bg-slate-950 border border-slate-700 px-2.5 py-1.5 text-xs text-white">
                                    @foreach([7 => '7 días', 14 => '14 días', 30 => '30 días'] as $d => $label)
                                        <option value="{{ $d }}" @selected((int) $item['duracion_dias'] === $d)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            @endif
                            <button type="submit" class="px-2.5 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-200">
                                Actualizar
                            </button>
                        </form>

                        <form action="{{ route('cart.remove', $item['key']) }}" method="POST">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-2.5 py-1.5 rounded-xl bg-rose-950/70 hover:bg-rose-800 text-rose-200 text-xs font-bold" title="Quitar">🗑️</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Formulario Checkout -->
        <div class="lg:col-span-5 bg-slate-900/95 rounded-3xl border border-slate-800 p-6 shadow-2xl">
            <h2 class="text-base font-extrabold text-white mb-4">💳 Finalizar Pedido y Pago</h2>

            <form action="{{ route('cart.checkout') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Entrega -->
                <div>
                    <span class="block text-xs font-extrabold uppercase text-slate-300 mb-2">1. Modalidad de Entrega</span>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="cursor-pointer">
                            <input type="radio" name="tipo_entrega" value="tienda" class="peer sr-only" @checked(old('tipo_entrega', 'tienda') === 'tienda') onchange="toggleDelivery()">
                            <div class="rounded-xl border border-slate-700 bg-slate-950 p-3 text-xs font-bold text-slate-200 peer-checked:border-amber-400 peer-checked:bg-amber-400/15 peer-checked:text-amber-300">
                                🏪 Recojo en Tienda (QR)
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="tipo_entrega" value="delivery" class="peer sr-only" @checked(old('tipo_entrega') === 'delivery') onchange="toggleDelivery()">
                            <div class="rounded-xl border border-slate-700 bg-slate-950 p-3 text-xs font-bold text-slate-200 peer-checked:border-amber-400 peer-checked:bg-amber-400/15 peer-checked:text-amber-300">
                                🛵 Delivery Lima / Perú
                            </div>
                        </label>
                    </div>
                    <div id="delivery-box" class="hidden mt-2">
                        <input type="text" name="direccion_entrega" value="{{ old('direccion_entrega') }}" placeholder="Dirección exacta, distrito y referencia..."
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-xs text-white">
                    </div>
                </div>

                <!-- Canje de Puntos Nova -->
                @if($reader && $maxDescuentoKarma > 0)
                    <div class="bg-amber-950/30 border border-amber-400/40 rounded-2xl p-3.5">
                        <label class="flex items-start gap-2.5 cursor-pointer text-xs">
                            <input type="checkbox" id="usar_karma" name="usar_karma" value="1" @checked(old('usar_karma')) onchange="recalcularTotalCarrito()"
                                   class="mt-0.5 rounded border-slate-600 bg-slate-950 text-amber-400">
                            <div>
                                <span class="font-extrabold text-amber-300">✨ Canjear Puntos Nova (-S/ {{ number_format($maxDescuentoKarma, 2) }})</span>
                                <span class="block text-[11px] text-slate-300 mt-0.5">Tienes {{ $reader->puntos_karma }} pts (10 pts = S/ 1.00, hasta 50% de descuento).</span>
                            </div>
                        </label>
                    </div>
                @endif

                <!-- Método de pago -->
                <div>
                    <span class="block text-xs font-extrabold uppercase text-slate-300 mb-2">2. Medio de Pago</span>
                    <div class="space-y-2">
                        <label class="cursor-pointer block">
                            <input type="radio" name="metodo_pago" value="yape_plin" class="peer sr-only" @checked(old('metodo_pago', 'yape_plin') === 'yape_plin') onchange="recalcularTotalCarrito()">
                            <div class="rounded-xl border border-slate-700 bg-slate-950 p-3 text-xs peer-checked:border-amber-400 peer-checked:bg-amber-400/10">
                                <span class="font-extrabold text-white">📱 Yape / Plin (Validación por Código de Operación)</span>
                            </div>
                        </label>

                        <label class="cursor-pointer block">
                            <input type="radio" name="metodo_pago" value="tarjeta" class="peer sr-only" @checked(old('metodo_pago') === 'tarjeta') onchange="recalcularTotalCarrito()">
                            <div class="rounded-xl border border-slate-700 bg-slate-950 p-3 text-xs peer-checked:border-emerald-400 peer-checked:bg-emerald-500/10">
                                <span class="font-extrabold text-white">💳 Tarjeta Crédito / Débito (Pasarela Sandbox)</span>
                            </div>
                        </label>

                        <label class="cursor-pointer block">
                            <input type="radio" name="metodo_pago" value="mostrador" class="peer sr-only" @checked(old('metodo_pago') === 'mostrador') onchange="recalcularTotalCarrito()">
                            <div class="rounded-xl border border-slate-700 bg-slate-950 p-3 text-xs peer-checked:border-amber-400 peer-checked:bg-amber-400/10">
                                <span class="font-extrabold text-white">🏪 Pago en Mostrador al Recoger (Expira en 24h)</span>
                            </div>
                        </label>

                        @if($soloAlquileres && $reader && $reader->puedeUsarMembresia())
                            <label class="cursor-pointer block">
                                <input type="radio" name="metodo_pago" value="membresia" class="peer sr-only" @checked(old('metodo_pago') === 'membresia') onchange="recalcularTotalCarrito()">
                                <div class="rounded-xl border border-emerald-500/40 bg-emerald-950/30 p-3 text-xs peer-checked:border-emerald-400 peer-checked:bg-emerald-500/20">
                                    <span class="font-extrabold text-emerald-300">🎟️ Incluido en tu {{ $reader->etiquetaPlan() }} (S/ 0.00)</span>
                                </div>
                            </label>
                        @endif
                    </div>
                </div>

                <!-- Detalles Yape/Plin -->
                <div id="checkout-yape-box" class="bg-slate-950 border border-amber-400/30 rounded-2xl p-4 text-xs space-y-2">
                    <p class="font-bold text-amber-300">📱 Yape / Plin NOVAETHER™ · 999-888-777</p>
                    <p class="text-slate-300">Transfiere el total y registra tu código de operación. El stock queda reservado por 24h hasta confirmación de caja.</p>
                    <input type="text" name="codigo_operacion" value="{{ old('codigo_operacion') }}" placeholder="N° de operación Yape/Plin (opcional si pagas luego)"
                           class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-xs text-white">
                </div>

                <!-- Detalles Tarjeta -->
                <div id="checkout-card-box" class="hidden bg-slate-950 border border-emerald-500/30 rounded-2xl p-4 text-xs space-y-2.5">
                    <p class="font-bold text-emerald-300">💳 Pasarela de Pago Tarjeta (Confirmación Instantánea)</p>
                    <input type="text" name="card_number" placeholder="Número de tarjeta (Ej. 4557 8800 1234 5678)"
                           class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-xs text-white">
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="card_expiry" placeholder="MM/AA"
                               class="rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-xs text-white">
                        <input type="text" name="card_cvv" placeholder="CVV"
                               class="rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-xs text-white">
                    </div>
                </div>

                <div class="border-t border-slate-800 pt-4 space-y-1.5 text-xs">
                    <div class="flex justify-between text-slate-300">
                        <span>Subtotal</span>
                        <span>S/ {{ number_format($subtotal, 2) }}</span>
                    </div>
                    <div id="descuento-row" class="hidden flex justify-between text-emerald-300 font-bold">
                        <span>Descuento Puntos Nova</span>
                        <span>− S/ {{ number_format($maxDescuentoKarma, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-base font-black text-white pt-1">
                        <span>Total a Pagar</span>
                        <span id="cart-total-display" class="text-amber-400">S/ {{ number_format($subtotal, 2) }}</span>
                    </div>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black py-3 rounded-xl text-xs shadow-lg transition">
                    Confirmar Orden y Emitir Comprobante →
                </button>
            </form>
        </div>
    </div>

    <script>
        const subtotalBase = {{ number_format($subtotal, 2, '.', '') }};
        const maxDescKarma = {{ number_format($maxDescuentoKarma, 2, '.', '') }};

        function toggleDelivery() {
            const val = document.querySelector('input[name="tipo_entrega"]:checked')?.value;
            document.getElementById('delivery-box').classList.toggle('hidden', val !== 'delivery');
        }

        function recalcularTotalCarrito() {
            const metodo = document.querySelector('input[name="metodo_pago"]:checked')?.value || 'yape_plin';
            const usarKarma = document.getElementById('usar_karma')?.checked || false;

            document.getElementById('checkout-yape-box').classList.toggle('hidden', metodo !== 'yape_plin');
            document.getElementById('checkout-card-box').classList.toggle('hidden', metodo !== 'tarjeta');

            let total = subtotalBase;
            const descRow = document.getElementById('descuento-row');

            if (metodo === 'membresia') {
                total = 0;
                if (descRow) descRow.classList.add('hidden');
            } else {
                const desc = usarKarma ? maxDescKarma : 0;
                total = Math.max(0, subtotalBase - desc);
                if (descRow) descRow.classList.toggle('hidden', !usarKarma || desc <= 0);
            }

            document.getElementById('cart-total-display').textContent = 'S/ ' + total.toFixed(2);
        }

        document.addEventListener('DOMContentLoaded', () => {
            toggleDelivery();
            recalcularTotalCarrito();
        });
    </script>
@endif
@endsection
