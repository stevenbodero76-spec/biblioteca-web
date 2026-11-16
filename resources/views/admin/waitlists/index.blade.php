@extends('layouts.app')

@section('title', 'Listas de Espera · Admin NOVAETHER™')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-white">🔔 Gestión de Listas de Espera y Demanda</h1>
        <p class="text-sm text-slate-300">Visualiza qué obras agotadas tienen mayor demanda insatisfecha para priorizar reposición.</p>
    </div>
    <a href="{{ route('dashboard') }}" class="text-xs font-extrabold uppercase text-amber-400 hover:underline">← Volver al Panel</a>
</div>

@if($topDemanda->isNotEmpty())
<div class="mb-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach($topDemanda as $libro)
        <div class="bg-slate-900 rounded-2xl border border-amber-400/30 p-4 flex items-center justify-between gap-3">
            <div class="min-w-0">
                <span class="text-[10px] font-black uppercase text-amber-300">{{ $libro->category->icono }} {{ $libro->category->nombre }}</span>
                <p class="font-bold text-sm text-white truncate mt-0.5">{{ $libro->titulo }}</p>
                <p class="text-xs text-slate-300">Stock actual: <strong>{{ $libro->stock_disponible }}/{{ $libro->stock_total }}</strong> · <strong class="text-amber-300">{{ $libro->esperando_count }} en cola</strong></p>
            </div>
            <a href="{{ route('books.edit', $libro) }}" class="bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black px-3 py-2 rounded-xl flex-shrink-0">
                Reponer →
            </a>
        </div>
    @endforeach
</div>
@endif

<div class="bg-slate-900/95 rounded-2xl border border-slate-800 shadow-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-950 border-b border-slate-800 text-xs uppercase text-slate-300">
                    <th class="py-3.5 px-4">Obra</th>
                    <th class="py-3.5 px-4">Cliente</th>
                    <th class="py-3.5 px-4">Estado</th>
                    <th class="py-3.5 px-4">Solicitud</th>
                    <th class="py-3.5 px-4 text-right">Acción</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/80">
                @forelse($waitlists as $wl)
                    <tr class="hover:bg-slate-800/40">
                        <td class="py-3.5 px-4">
                            <p class="font-bold text-white">{{ $wl->book->titulo }}</p>
                            <span class="text-xs text-slate-400">Stock: {{ $wl->book->stock_disponible }}/{{ $wl->book->stock_total }}</span>
                        </td>
                        <td class="py-3.5 px-4 text-xs">
                            <p class="font-bold text-slate-100">{{ $wl->reader->nombre_completo }}</p>
                            <p class="text-slate-400">{{ $wl->reader->email }} · {{ $wl->reader->etiquetaPlan() }}</p>
                        </td>
                        <td class="py-3.5 px-4">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold {{ $wl->estado === 'notificado' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300' }}">
                                {{ $wl->estado === 'notificado' ? 'Notificado (Disponible)' : 'En Espera' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-xs text-slate-300">{{ $wl->created_at->format('d/m/Y H:i') }}</td>
                        <td class="py-3.5 px-4 text-right">
                            <form action="{{ route('waitlists.destroy', $wl) }}" method="POST" data-confirm="¿Retirar turno de espera de {{ $wl->reader->nombre_completo }}?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-bold text-rose-400 hover:underline">Cancelar turno</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-slate-300">No hay clientes actualmente en listas de espera.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $waitlists->links() }}</div>
@endsection
