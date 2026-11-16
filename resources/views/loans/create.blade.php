@extends('layouts.app')

@section('title', 'Reservar / Registrar Préstamo · NOVAETHER™')

@section('content')
@php
    $clientePuedeClub = auth()->user()->esAdmin() || ($miPerfilReader && $miPerfilReader->puedeUsarMembresia());
    $metodoDefault = $clientePuedeClub ? 'membresia' : 'yape_plin';
@endphp

<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-white">🔥 {{ auth()->user()->esCliente() ? 'Alquiler Rápido en 1 Clic' : 'Registrar Préstamo' }}</h1>
            <p class="text-sm text-slate-300">Verificación atómica de inventario + Ticket QR oficial (vigencia de pago 24h).</p>
        </div>
        <a href="{{ route('loans.index') }}" class="text-xs font-extrabold uppercase text-amber-400 hover:underline">← {{ auth()->user()->esCliente() ? 'Mis Tickets QR' : 'Ver Préstamos' }}</a>
    </div>

    <div class="mb-5 bg-slate-900 border border-amber-400/30 rounded-2xl p-4 text-xs text-slate-200">
        <strong class="text-amber-300">⚡ Protección de Inventario:</strong> Máximo 3 alquileres activos simultáneos. Las reservas Yape/Plin o en mostrador expiran automáticamente a las 24h si no se confirma el pago.
    </div>

    <form action="{{ route('loans.store') }}" method="POST" class="bg-slate-900/95 rounded-3xl border border-slate-800 p-6 sm:p-8 shadow-2xl space-y-5">
        @csrf

        <!-- Buscador en tiempo real del selector de libros -->
        <div>
            <label for="filtro_libro" class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">1. Seleccionar Obra del Catálogo *</label>
            <input type="text" id="filtro_libro" placeholder="🔎 Escribe título o autor para filtrar rápidamente..."
                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-xs text-white placeholder-slate-400 mb-2 focus:border-amber-400 focus:outline-none">

            <select id="book_id" name="book_id" required class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
                <option value="" data-precio="0">Seleccione un libro, cómic o manga...</option>
                @foreach($books as $book)
                    <option value="{{ $book->id }}"
                            data-precio="{{ number_format((float) $book->precio_alquiler, 2, '.', '') }}"
                            data-search="{{ mb_strtolower($book->titulo . ' ' . $book->autor . ' ' . $book->isbn) }}"
                            @selected(old('book_id', $selectedBookId) == $book->id)>
                        {{ $book->estaDisponible() ? '🟢' : '🔴 [SIN STOCK]' }}
                        {{ $book->titulo }} — {{ $book->autor }}
                        (Disp: {{ $book->stock_disponible }}/{{ $book->stock_total }} · S/ {{ number_format((float) $book->precio_alquiler, 2) }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Solicitante -->
        @if(auth()->user()->esAdmin())
            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">2. Cliente / Miembro Solicitante *</label>
                <select name="reader_id" required class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
                    <option value="">Seleccione un cliente registrado...</option>
                    @foreach($readers as $reader)
                        <option value="{{ $reader->id }}" @selected(old('reader_id') == $reader->id)>
                            {{ $reader->estado === 'activo' ? '🟢' : '🔴 [SUSPENDIDO]' }}
                            {{ $reader->nombre_completo }} — Doc: {{ $reader->dni_codigo }} ({{ $reader->etiquetaPlan() }})
                        </option>
                    @endforeach
                </select>
            </div>
        @else
            <div class="bg-slate-950 border border-slate-800 rounded-2xl p-4 flex items-center justify-between gap-3">
                <div>
                    <span class="block text-[10px] font-black uppercase tracking-wider text-amber-400 mb-1">2. Titular Verificado</span>
                    @if($miPerfilReader)
                        <input type="hidden" name="reader_id" value="{{ $miPerfilReader->id }}">
                        <p class="text-sm font-bold text-white">{{ $miPerfilReader->nombre_completo }}</p>
                        <p class="text-xs text-slate-300 mt-0.5">Doc: {{ $miPerfilReader->dni_codigo }} · {{ $miPerfilReader->etiquetaPlan() }}</p>
                    @else
                        <p class="text-xs text-rose-300">⚠️ Tu cuenta no posee una ficha de lector vinculada.</p>
                    @endif
                </div>
                @if($miPerfilReader)
                    <span class="px-3 py-1 rounded-xl bg-amber-400/15 border border-amber-400/30 text-amber-300 text-xs font-black">
                        ✨ {{ $miPerfilReader->puntos_karma }} pts
                    </span>
                @endif
            </div>
        @endif

        <!-- Plazo -->
        @if(auth()->user()->esCliente())
            <div>
                <span class="block text-xs font-extrabold uppercase text-slate-300 mb-2">3. Duración del Préstamo *</span>
                <div class="grid grid-cols-3 gap-2.5">
                    @foreach([7 => '⚡ Express (7 días)', 14 => '📖 Estándar (14 días)', 30 => '🏆 Extendido (30 días)'] as $dias => $etiqueta)
                        <label class="cursor-pointer">
                            <input type="radio" name="duracion_dias" value="{{ $dias }}" class="peer sr-only" @checked(old('duracion_dias', 7) == $dias)>
                            <div class="text-center rounded-xl border border-slate-700 bg-slate-950 p-3 text-xs font-bold text-slate-200 peer-checked:border-amber-400 peer-checked:bg-amber-400/15 peer-checked:text-amber-300 transition">
                                {{ $etiqueta }}
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Fecha de Salida / Reserva *</label>
                    <input type="date" name="fecha_prestamo" value="{{ old('fecha_prestamo', now()->toDateString()) }}"
                           min="{{ now()->subDay()->toDateString() }}" max="{{ now()->addDays(7)->toDateString() }}" required
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
                </div>

                <div>
                    <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Fecha Límite (Máx. 30 días) *</label>
                    <input type="date" name="fecha_devolucion_esperada" value="{{ old('fecha_devolucion_esperada', now()->addDays(7)->toDateString()) }}" required
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
                </div>
            </div>
        @endif

        <!-- Modalidad de Pago en Soles -->
        <div>
            <span class="block text-xs font-extrabold uppercase text-slate-300 mb-2">{{ auth()->user()->esCliente() ? '4.' : '3.' }} Modalidad de Pago *</span>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
                @if($clientePuedeClub)
                    <label class="cursor-pointer">
                        <input type="radio" name="metodo_pago" value="membresia" class="peer sr-only" @checked(old('metodo_pago', $metodoDefault) === 'membresia') onchange="actualizarResumenPago()">
                        <div class="rounded-xl border border-slate-700 bg-slate-950 p-3 text-xs peer-checked:border-emerald-400 peer-checked:bg-emerald-500/15 transition">
                            <p class="font-extrabold text-white">🎟️ Club NovaEther</p>
                            <p class="text-emerald-300 font-black mt-0.5">S/ 0.00 (Incluido)</p>
                        </div>
                    </label>
                @else
                    <a href="{{ route('plans') }}" class="rounded-xl border border-dashed border-slate-700 bg-slate-950/50 p-3 text-xs hover:border-amber-400 transition block">
                        <p class="font-extrabold text-slate-300">🎟️ Club NovaEther</p>
                        <p class="text-amber-400 font-bold mt-0.5 text-[11px]">Activa un plan aquí →</p>
                    </a>
                @endif

                <label class="cursor-pointer">
                    <input type="radio" name="metodo_pago" value="yape_plin" class="peer sr-only" @checked(old('metodo_pago', $metodoDefault) === 'yape_plin') onchange="actualizarResumenPago()">
                    <div class="rounded-xl border border-slate-700 bg-slate-950 p-3 text-xs peer-checked:border-amber-400 peer-checked:bg-amber-400/15 transition">
                        <p class="font-extrabold text-white">📱 Yape / Plin QR</p>
                        <p class="text-amber-300 font-black mt-0.5" data-precio-alquiler>S/ 0.00</p>
                    </div>
                </label>

                <label class="cursor-pointer">
                    <input type="radio" name="metodo_pago" value="mostrador" class="peer sr-only" @checked(old('metodo_pago', $metodoDefault) === 'mostrador') onchange="actualizarResumenPago()">
                    <div class="rounded-xl border border-slate-700 bg-slate-950 p-3 text-xs peer-checked:border-amber-400 peer-checked:bg-amber-400/15 transition">
                        <p class="font-extrabold text-white">🏪 En Mostrador</p>
                        <p class="text-amber-300 font-black mt-0.5" data-precio-alquiler>S/ 0.00</p>
                    </div>
                </label>
            </div>

            <div id="yape-demo-box" class="hidden mt-3 rounded-2xl bg-slate-950 border border-amber-400/40 p-4 text-xs text-slate-200 space-y-2.5">
                <p class="font-extrabold text-amber-300">📱 Transferencia Yape / Plin · Número NOVAETHER™: 999-888-777</p>
                <p>Monto exacto: <strong id="yape-monto-texto" class="text-white">S/ 0.00</strong>. Ingresa tu número de operación para validación rápida (o déjalo vacío y paga antes de 24h).</p>
                <input type="text" name="codigo_operacion" value="{{ old('codigo_operacion') }}" placeholder="N° de operación Yape / Plin (Ej. 482910)"
                       class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-xs text-white">
            </div>
        </div>

        @if(auth()->user()->esAdmin())
            <div class="space-y-3 border-t border-slate-800 pt-3">
                <label class="flex items-center gap-2 text-xs font-bold text-emerald-300 cursor-pointer">
                    <input type="checkbox" name="pago_verificado_admin" value="1" checked class="rounded border-slate-600 bg-slate-950 text-emerald-400">
                    <span>✓ Pago cobrado / verificado en mostrador en este acto</span>
                </label>

                <div>
                    <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Notas / Observaciones (Uso interno)</label>
                    <textarea name="observaciones" rows="2" placeholder="Ej. Entrega de edición física sellada."
                              class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-400">{{ old('observaciones') }}</textarea>
                </div>
            </div>
        @endif

        <div class="pt-3 flex items-center justify-between gap-2 border-t border-slate-800">
            <div class="text-xs">
                <span class="text-slate-400">Total a pagar:</span>
                <strong id="total-resumen" class="text-base font-black text-emerald-400 ml-1">S/ 0.00</strong>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('loans.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-700 text-xs font-bold text-slate-200 hover:bg-slate-800">Cancelar</a>
                <button type="submit" class="bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 text-xs font-black px-6 py-2.5 rounded-xl shadow-lg">
                    Confirmar y Generar Ticket QR
                </button>
            </div>
        </div>
    </form>
</div>

<script>
function actualizarResumenPago() {
    const select = document.getElementById('book_id');
    const selected = select.options[select.selectedIndex];
    const precio = parseFloat(selected?.dataset?.precio || '0') || 0;

    document.querySelectorAll('[data-precio-alquiler]').forEach((el) => {
        el.textContent = 'S/ ' + precio.toFixed(2);
    });

    const metodo = document.querySelector('input[name="metodo_pago"]:checked')?.value || 'yape_plin';
    const total = metodo === 'membresia' ? 0 : precio;

    const totalEl = document.getElementById('total-resumen');
    totalEl.textContent = 'S/ ' + total.toFixed(2);
    totalEl.className = total === 0 ? 'text-base font-black text-emerald-400 ml-1' : 'text-base font-black text-amber-400 ml-1';

    const yapeBox = document.getElementById('yape-demo-box');
    const yapeMonto = document.getElementById('yape-monto-texto');
    if (metodo === 'yape_plin') {
        yapeBox.classList.remove('hidden');
        yapeMonto.textContent = 'S/ ' + total.toFixed(2);
    } else {
        yapeBox.classList.add('hidden');
    }
}

document.getElementById('book_id').addEventListener('change', actualizarResumenPago);
document.getElementById('filtro_libro').addEventListener('input', function () {
    const termino = this.value.trim().toLowerCase();
    const select = document.getElementById('book_id');
    Array.from(select.options).forEach((opt) => {
        if (!opt.value) return;
        const text = opt.dataset.search || opt.textContent.toLowerCase();
        opt.hidden = termino !== '' && !text.includes(termino);
    });
});

document.addEventListener('DOMContentLoaded', actualizarResumenPago);
</script>
@endsection
