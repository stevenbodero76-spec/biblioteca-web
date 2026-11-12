@extends('layouts.app')

@section('title', 'Reservas y Préstamos · NOVAETHER™')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-white">🔄 Control de Reservas, Préstamos y Devoluciones</h1>
        <p class="text-sm text-slate-400">Gestión de salidas, devoluciones y actualización automática de stock en MySQL.</p>
    </div>
    <a href="{{ route('loans.create') }}" class="bg-gradient-to-r from-amber-400 to-orange-500 text-slate-950 text-xs font-black px-4 py-2.5 rounded-xl shadow transition">
        🔥 Nueva Reserva / Préstamo
    </a>
</div>

<form method="GET" action="{{ route('loans.index') }}" class="bg-[#0E1526] rounded-2xl border border-slate-800 p-4 mb-6 shadow-xl">
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        <div class="sm:col-span-2">
            <label class="block text-[11px] font-extrabold uppercase text-slate-400 mb-1">Buscar por obra, ISBN, cliente o documento</label>
            <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Ej. One Piece, Dune, 72849102..."
                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white placeholder-slate-500">
        </div>
        <div>
            <label class="block text-[11px] font-extrabold uppercase text-slate-400 mb-1">Estado</label>
            <select name="estado" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                <option value="">Todos los estados</option>
                <option value="prestado" @selected(($filtros['estado'] ?? '') === 'prestado')>🟡 En Curso (Prestado)</option>
                <option value="vencido" @selected(($filtros['estado'] ?? '') === 'vencido')>🔴 Vencido</option>
                <option value="devuelto" @selected(($filtros['estado'] ?? '') === 'devuelto')>🟢 Devuelto</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black py-2.5 px-3 rounded-xl">
                🔍 Filtrar
            </button>
            <a href="{{ route('loans.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold py-2.5 px-3 rounded-xl">
                Limpiar
            </a>
        </div>
    </div>
</form>

<div class="bg-[#0E1526] rounded-2xl border border-slate-800 shadow-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-[#090D18] border-b border-slate-800 text-xs uppercase text-slate-400">
                    <th class="py-3.5 px-4">#</th>
                    <th class="py-3.5 px-4">Obra y Stock Actual</th>
                    <th class="py-3.5 px-4">Cliente / Miembro</th>
                    <th class="py-3.5 px-4">Fechas</th>
                    <th class="py-3.5 px-4">Registrado por</th>
                    <th class="py-3.5 px-4">Estado</th>
                    <th class="py-3.5 px-4 text-right">Operación</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/80">
                @forelse($loans as $loan)
                    <tr class="hover:bg-slate-900/60">
                        <td class="py-3.5 px-4 font-mono text-xs text-slate-500">#{{ $loan->id }}</td>
                        <td class="py-3.5 px-4">
                            <p class="font-bold text-white">{{ $loan->book->titulo }}</p>
                            <p class="text-xs text-slate-400">
                                {{ $loan->book->category->icono }} {{ $loan->book->category->nombre }} ·
                                Stock: <strong class="text-amber-300">{{ $loan->book->stock_disponible }}/{{ $loan->book->stock_total }}</strong>
                            </p>
                        </td>
                        <td class="py-3.5 px-4">
                            <p class="font-semibold text-slate-200">{{ $loan->reader->nombre_completo }}</p>
                            <p class="text-xs text-slate-500">Doc: {{ $loan->reader->dni_codigo }}</p>
                        </td>
                        <td class="py-3.5 px-4 text-xs text-slate-400">
                            <div><strong>Salida:</strong> {{ $loan->fecha_prestamo->format('d/m/Y') }}</div>
                            <div><strong>Límite:</strong> {{ $loan->fecha_devolucion_esperada->format('d/m/Y') }}</div>
                            @if($loan->fecha_devolucion_real)
                                <div class="text-emerald-400 font-bold"><strong>Devuelto:</strong> {{ $loan->fecha_devolucion_real->format('d/m/Y') }}</div>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-xs text-slate-400">
                            {{ $loan->user->name }}
                            <span class="block text-[10px] uppercase text-slate-500">{{ $loan->user->rol }}</span>
                        </td>
                        <td class="py-3.5 px-4">
                            @if($loan->estado === 'devuelto')
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300">Devuelto</span>
                            @elseif($loan->estado === 'vencido')
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500/20 text-rose-300">Vencido</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300">En Curso</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right space-x-1">
                            @if($loan->estado !== 'devuelto')
                                <form action="{{ route('loans.devolver', $loan) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-extrabold px-3 py-1.5 rounded-lg shadow transition">
                                        ✅ Devolver (+1 Stock)
                                    </button>
                                </form>
                            @endif

                            @if(auth()->user()->esAdmin())
                                <form action="{{ route('loans.destroy', $loan) }}" method="POST" class="inline"
                                      onsubmit="return confirm('¿Eliminar este registro?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-rose-400 hover:text-rose-300 px-2 py-1">🗑️</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-500">No se encontraron reservas o préstamos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $loans->links() }}
</div>
@endsection
