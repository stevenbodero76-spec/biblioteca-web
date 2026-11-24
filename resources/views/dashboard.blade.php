@extends('layouts.app')

@section('title', 'Panel Ejecutivo · NOVAETHER™')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-white">📊 Panel Ejecutivo &amp; Operaciones</h1>
        <p class="text-sm text-slate-300">Inventario en vivo, préstamos y atención a lectores.</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('admin.books.index') }}" class="bg-slate-800 hover:bg-slate-700 text-emerald-300 border border-slate-700 text-xs font-bold px-3.5 py-2.5 rounded-xl transition">
            📚 Libros
        </a>
        <a href="{{ route('books.create') }}" class="bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-200 border border-emerald-500/40 text-xs font-black px-3.5 py-2.5 rounded-xl transition">
            ➕ Subir Libro
        </a>
        <a href="{{ route('admin.categories.index') }}" class="bg-slate-800 hover:bg-slate-700 text-emerald-300 border border-slate-700 text-xs font-bold px-3.5 py-2.5 rounded-xl transition">
            🗂️ Categorías
        </a>
        <a href="{{ route('admin.complaints.index') }}" class="bg-slate-800 hover:bg-slate-700 text-amber-300 border border-slate-700 text-xs font-bold px-3.5 py-2.5 rounded-xl transition">
            📖 Reclamaciones ({{ $resumen['reclamos_abiertos'] }})
        </a>
        <a href="{{ route('admin.waitlists.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-bold px-3.5 py-2.5 rounded-xl transition">
            🔔 Espera ({{ $resumen['en_lista_espera'] }})
        </a>
        <a href="{{ route('admin.reviews.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-bold px-3.5 py-2.5 rounded-xl transition">
            💬 Reseñas ({{ $resumen['resenas_totales'] }})
        </a>
        <a href="{{ route('loans.create') }}" class="bg-linear-to-r from-amber-400 to-orange-500 text-slate-950 text-xs font-black px-4 py-2.5 rounded-xl shadow transition">
            🔥 Nuevo Préstamo
        </a>
    </div>
</div>

<!-- KPIs de inventario y servicio -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-slate-900 rounded-2xl border border-slate-800 p-4 shadow-lg">
        <p class="text-[11px] font-extrabold uppercase text-slate-300">Stock / Títulos</p>
        <p class="text-2xl font-black text-white mt-1">{{ $resumen['ejemplares_disponibles'] }} <span class="text-xs font-normal text-slate-400">/ {{ $resumen['total_ejemplares'] }}</span></p>
        <p class="text-xs text-slate-300 mt-1">📚 {{ $resumen['total_titulos'] }} obras ({{ $resumen['libros_agotados'] }} agot.)</p>
    </div>

    <div class="bg-slate-900 rounded-2xl border border-slate-800 p-4 shadow-lg">
        <p class="text-[11px] font-extrabold uppercase text-slate-300">Préstamos / Mora</p>
        <p class="text-2xl font-black text-white mt-1">{{ $resumen['prestamos_activos'] }} <span class="text-sm text-rose-400">({{ $resumen['prestamos_vencidos'] }} venc.)</span></p>
        <p class="text-xs text-emerald-400 font-semibold mt-1">✅ {{ $resumen['devoluciones_completadas'] }} devueltos</p>
    </div>

    <div class="bg-slate-900 rounded-2xl border border-slate-800 p-4 shadow-lg">
        <p class="text-[11px] font-extrabold uppercase text-slate-300">Lectores &amp; Atención</p>
        <p class="text-2xl font-black text-white mt-1">{{ $resumen['lectores_activos'] }}</p>
        <p class="text-xs text-amber-300 font-semibold mt-1">📖 {{ $resumen['reclamos_abiertos'] }} reclamos abiertos</p>
    </div>

    <div class="bg-slate-900 rounded-2xl border border-slate-800 p-4 shadow-lg">
        <p class="text-[11px] font-extrabold uppercase text-slate-300">Comunidad &amp; Cola</p>
        <p class="text-2xl font-black text-white mt-1">{{ $resumen['en_lista_espera'] }} <span class="text-xs font-normal text-slate-400">en espera</span></p>
        <p class="text-xs text-sky-300 font-semibold mt-1">💬 {{ $resumen['resenas_totales'] }} reseñas</p>
    </div>
</div>

<!-- Alerta de Títulos Agotados: Reposición rápida de inventario -->
@if($librosSinStock->isNotEmpty())
<div class="mb-6 bg-rose-950/50 border border-rose-500/40 rounded-2xl p-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-sm font-extrabold text-rose-200">📦 Títulos Sin Stock — Prioridad de Reposición</h2>
            <p class="text-xs text-rose-200/80">Obras con 0 unidades disponibles actualmente. Reabastece el inventario para liberar la lista de espera.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach($librosSinStock as $agotado)
                <a href="{{ route('books.edit', $agotado) }}"
                   class="text-xs bg-rose-500/20 hover:bg-rose-500/35 border border-rose-400/40 text-rose-100 font-bold px-3 py-1.5 rounded-xl transition">
                    Reponer «{{ $agotado->titulo }}» ({{ $agotado->en_espera_count }} en espera) →
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Reporte por las 12 Categorías -->
    <div class="bg-slate-900 rounded-2xl border border-slate-800 shadow-lg p-5">
        <h2 class="text-base font-extrabold text-white mb-3">🗂️ Inventario por Categorías</h2>
        <div class="space-y-2.5 max-h-96 overflow-y-auto pr-1">
            @foreach($categoriasResumen as $cat)
                <a href="{{ route('books.index', ['category_id' => $cat->id]) }}"
                   class="flex items-center justify-between p-2.5 rounded-xl bg-slate-950 border border-slate-800 hover:border-amber-400/50 transition">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="text-lg">{{ $cat->icono }}</span>
                        <div class="min-w-0">
                            <p class="text-xs font-bold text-slate-100 truncate">{{ $cat->nombre }}</p>
                            <p class="text-[11px] text-slate-400">{{ $cat->books_count }} título(s)</p>
                        </div>
                    </div>
                    <span class="text-xs font-black px-2.5 py-1 rounded-full flex-shrink-0 {{ ($cat->books_sum_stock_disponible ?? 0) > 0 ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-200' }}">
                        {{ $cat->books_sum_stock_disponible ?? 0 }} disp.
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Últimos Préstamos con Acción Rápida 1-Clic -->
    <div class="lg:col-span-2 bg-slate-900 rounded-2xl border border-slate-800 shadow-lg p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-extrabold text-white">📋 Últimos Préstamos</h2>
            <a href="{{ route('loans.index') }}" class="text-xs font-bold text-amber-400 hover:underline">Ver historial completo →</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-800 text-xs uppercase text-slate-300">
                        <th class="py-2.5 px-2">Obra</th>
                        <th class="py-2.5 px-2">Lector</th>
                        <th class="py-2.5 px-2">Estado</th>
                        <th class="py-2.5 px-2 text-right">Acción Rápida</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    @foreach($ultimosPrestamos as $loan)
                        <tr class="hover:bg-slate-800/40">
                            <td class="py-3 px-2">
                                <p class="font-bold text-white text-xs sm:text-sm">{{ $loan->book->titulo }}</p>
                                <p class="text-[11px] text-slate-400 font-mono">{{ $loan->codigoTicket() }}</p>
                            </td>
                            <td class="py-3 px-2">
                                <p class="font-medium text-slate-100 text-xs sm:text-sm">{{ $loan->reader->nombre_completo }}</p>
                                <p class="text-[11px] text-slate-400">Límite: {{ $loan->fecha_devolucion_esperada->format('d/m/Y') }}</p>
                            </td>
                            <td class="py-3 px-2">
                                <x-status-badge :estado="$loan->estaVencido() ? 'vencido' : $loan->estado" />
                            </td>
                            <td class="py-3 px-2 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('loans.ticket', $loan) }}" class="text-xs font-bold text-amber-300 hover:underline">🎫</a>
                                    @if($loan->estado !== 'devuelto')
                                        <form action="{{ route('loans.devolver', $loan) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-[11px] font-extrabold px-2.5 py-1 rounded-lg transition">
                                                Devolver
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
