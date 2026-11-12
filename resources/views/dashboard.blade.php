@extends('layouts.app')

@section('title', 'Analytics & Reporte General · NOVAETHER™')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-white">📊 Panel Ejecutivo & Reporte de Operaciones</h1>
        <p class="text-sm text-slate-400">Métricas en tiempo real conectadas a MySQL (XAMPP): inventario, membresías y circulación.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('loans.create') }}" class="bg-gradient-to-r from-amber-400 to-orange-500 text-slate-950 text-xs font-black px-4 py-2.5 rounded-xl shadow transition">
            🔥 Nueva Reserva / Préstamo
        </a>
        <a href="{{ route('books.index') }}" class="bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 text-xs font-bold px-4 py-2.5 rounded-xl transition">
            🌌 Ver Catálogo
        </a>
    </div>
</div>

<!-- Tarjetas de Indicadores (KPIs) -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-[#0E1526] rounded-2xl border border-slate-800 p-5 shadow-lg">
        <p class="text-xs font-extrabold uppercase text-slate-400">Obras / Unidades</p>
        <p class="text-2xl font-black text-white mt-1">{{ $resumen['total_titulos'] }} <span class="text-sm font-normal text-slate-400">({{ $resumen['total_ejemplares'] }} uds.)</span></p>
        <p class="text-xs text-emerald-400 font-bold mt-1">🟢 {{ $resumen['ejemplares_disponibles'] }} listas para entrega</p>
    </div>

    <div class="bg-[#0E1526] rounded-2xl border border-slate-800 p-5 shadow-lg">
        <p class="text-xs font-extrabold uppercase text-slate-400">Reservas Activas</p>
        <p class="text-2xl font-black text-amber-400 mt-1">{{ $resumen['prestamos_activos'] }}</p>
        <p class="text-xs text-slate-400 mt-1">🔄 {{ $resumen['devoluciones_completadas'] }} devoluciones completadas</p>
    </div>

    <div class="bg-[#0E1526] rounded-2xl border border-slate-800 p-5 shadow-lg">
        <p class="text-xs font-extrabold uppercase text-slate-400">Plazos Vencidos</p>
        <p class="text-2xl font-black text-rose-400 mt-1">{{ $resumen['prestamos_vencidos'] }}</p>
        <p class="text-xs text-rose-400 font-semibold mt-1">⚠️ Seguimiento prioritario</p>
    </div>

    <div class="bg-[#0E1526] rounded-2xl border border-slate-800 p-5 shadow-lg">
        <p class="text-xs font-extrabold uppercase text-slate-400">Títulos Agotados (Stock 0)</p>
        <p class="text-2xl font-black text-cyan-400 mt-1">{{ $resumen['libros_agotados'] }}</p>
        <p class="text-xs text-slate-400 mt-1">👥 {{ $resumen['lectores_activos'] }} clientes activos</p>
    </div>
</div>

<!-- Alerta de Libros sin Disponibilidad -->
@if($librosSinStock->isNotEmpty())
<div class="mb-6 bg-rose-950/60 border border-rose-500/40 rounded-2xl p-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h2 class="text-sm font-extrabold text-rose-200">🔒 Títulos Agotados (Protección de Stock Activa)</h2>
            <p class="text-xs text-rose-300/80">El sistema bloquea automáticamente cualquier intento de préstamo sobre obras con 0 unidades disponibles.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @foreach($librosSinStock as $agotado)
                <a href="{{ route('loans.create', ['book_id' => $agotado->id]) }}"
                   class="text-xs bg-rose-500/20 hover:bg-rose-500/30 border border-rose-400/40 text-rose-200 font-bold px-3 py-1.5 rounded-xl transition">
                    Probar bloqueo: «{{ $agotado->titulo }}» (0/{{ $agotado->stock_total }})
                </a>
            @endforeach
        </div>
    </div>
</div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Reporte por las 12 Categorías -->
    <div class="bg-[#0E1526] rounded-2xl border border-slate-800 shadow-lg p-5">
        <h2 class="text-base font-extrabold text-white mb-3">🗂️ Inventario por Categorías (12)</h2>
        <div class="space-y-2.5 max-h-96 overflow-y-auto pr-1">
            @foreach($categoriasResumen as $cat)
                <a href="{{ route('books.index', ['category_id' => $cat->id]) }}"
                   class="flex items-center justify-between p-2.5 rounded-xl bg-[#090D18] border border-slate-800 hover:border-amber-400/50 transition">
                    <div class="flex items-center gap-2.5">
                        <span class="text-lg">{{ $cat->icono }}</span>
                        <div>
                            <p class="text-xs font-bold text-slate-200">{{ $cat->nombre }}</p>
                            <p class="text-[11px] text-slate-500">{{ $cat->books_count }} título(s)</p>
                        </div>
                    </div>
                    <span class="text-xs font-black px-2.5 py-1 rounded-full {{ ($cat->books_sum_stock_disponible ?? 0) > 0 ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300' }}">
                        {{ $cat->books_sum_stock_disponible ?? 0 }} disp.
                    </span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- Últimos Préstamos y Devoluciones -->
    <div class="lg:col-span-2 bg-[#0E1526] rounded-2xl border border-slate-800 shadow-lg p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-extrabold text-white">📋 Últimos Movimientos y Reservas</h2>
            <a href="{{ route('loans.index') }}" class="text-xs font-bold text-amber-400 hover:underline">Ver historial completo →</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-800 text-xs uppercase text-slate-400">
                        <th class="py-2.5 px-2">Obra</th>
                        <th class="py-2.5 px-2">Cliente / Miembro</th>
                        <th class="py-2.5 px-2">Fechas</th>
                        <th class="py-2.5 px-2">Estado</th>
                        <th class="py-2.5 px-2 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/80">
                    @foreach($ultimosPrestamos as $loan)
                        <tr class="hover:bg-slate-900/60">
                            <td class="py-3 px-2">
                                <p class="font-bold text-white">{{ $loan->book->titulo }}</p>
                                <p class="text-xs text-slate-400">{{ $loan->book->category->icono }} {{ $loan->book->category->nombre }}</p>
                            </td>
                            <td class="py-3 px-2">
                                <p class="font-medium text-slate-200">{{ $loan->reader->nombre_completo }}</p>
                                <p class="text-xs text-slate-500">ID: {{ $loan->reader->dni_codigo }}</p>
                            </td>
                            <td class="py-3 px-2 text-xs text-slate-400">
                                <div>Salida: {{ $loan->fecha_prestamo->format('d/m/Y') }}</div>
                                <div>Límite: {{ $loan->fecha_devolucion_esperada->format('d/m/Y') }}</div>
                            </td>
                            <td class="py-3 px-2">
                                @if($loan->estado === 'devuelto')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300">Devuelto</span>
                                @elseif($loan->estado === 'vencido')
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500/20 text-rose-300">Vencido</span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300">En Curso</span>
                                @endif
                            </td>
                            <td class="py-3 px-2 text-right">
                                @if($loan->estado !== 'devuelto')
                                    <form action="{{ route('loans.devolver', $loan) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-extrabold px-3 py-1.5 rounded-lg transition">
                                            Devolver
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-500">Completado</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
