@extends('layouts.app')

@section('title', 'Gestión de Reclamaciones · Admin NOVAETHER™')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-white">📖 Gestión de Libro de Reclamaciones</h1>
        <p class="text-sm text-slate-300">Atiende reclamos y quejas oficiales registradas por consumidores y deja constancia de respuesta.</p>
    </div>
    <a href="{{ route('dashboard') }}" class="text-xs font-extrabold uppercase text-amber-400 hover:underline">← Volver al Panel</a>
</div>

<form method="GET" action="{{ route('admin.complaints.index') }}" class="bg-slate-900/95 rounded-2xl border border-slate-800 p-4 mb-6 shadow-xl">
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        <div>
            <label class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1">Buscar código / DNI / cliente</label>
            <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Ej. LRC-2026..."
                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white">
        </div>
        <div>
            <label class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1">Estado</label>
            <select name="estado" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                <option value="">Todos</option>
                <option value="registrado" @selected(($filtros['estado'] ?? '') === 'registrado')>Registrado</option>
                <option value="en_revision" @selected(($filtros['estado'] ?? '') === 'en_revision')>En Revisión</option>
                <option value="resuelto" @selected(($filtros['estado'] ?? '') === 'resuelto')>Resuelto</option>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1">Tipo</label>
            <select name="tipo" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                <option value="">Reclamos y Quejas</option>
                <option value="reclamo" @selected(($filtros['tipo'] ?? '') === 'reclamo')>Reclamo</option>
                <option value="queja" @selected(($filtros['tipo'] ?? '') === 'queja')>Queja</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black py-2.5 px-3 rounded-xl">🔍 Filtrar</button>
            <a href="{{ route('admin.complaints.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold py-2.5 px-3 rounded-xl">Limpiar</a>
        </div>
    </div>
</form>

<div class="space-y-4">
    @forelse($complaints as $item)
        <div class="bg-slate-900/95 rounded-2xl border border-slate-800 p-5 shadow-xl space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-800 pb-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-xs font-black text-amber-300 bg-slate-950 border border-amber-400/30 px-2.5 py-1 rounded-lg">{{ $item->codigo_reclamo }}</span>
                    <span class="text-xs font-black uppercase px-2.5 py-1 rounded-full {{ $item->tipo === 'reclamo' ? 'bg-rose-500/20 text-rose-200' : 'bg-sky-500/20 text-sky-200' }}">{{ $item->tipo }}</span>
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $item->estado === 'resuelto' ? 'bg-emerald-500/20 text-emerald-300' : ($item->estado === 'en_revision' ? 'bg-amber-500/20 text-amber-300' : 'bg-slate-800 text-slate-200') }}">
                        {{ strtoupper(str_replace('_', ' ', $item->estado)) }}
                    </span>
                </div>
                <span class="text-xs text-slate-400">{{ $item->created_at->format('d/m/Y H:i') }}</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                <div class="space-y-2">
                    <p class="text-slate-200"><strong>Consumidor:</strong> {{ $item->nombre_completo }} (DNI/CE: {{ $item->dni_ce }})</p>
                    <p class="text-slate-300"><strong>Contacto:</strong> {{ $item->email }} {{ $item->telefono ? '· ' . $item->telefono : '' }}</p>
                    <div class="bg-slate-950 rounded-xl p-3 border border-slate-800">
                        <p class="text-[10px] font-black uppercase text-slate-400 mb-1">Detalle del consumidor</p>
                        <p class="text-slate-200 leading-relaxed">{{ $item->detalle }}</p>
                        <p class="text-amber-300 font-semibold mt-2">Solicita: {{ $item->pedido_consumidor }}</p>
                    </div>
                </div>

                <form action="{{ route('admin.complaints.update', $item) }}" method="POST" class="bg-slate-950 rounded-xl p-4 border border-slate-800 space-y-3">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 mb-1">Actualizar Estado</label>
                        <select name="estado" class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-xs text-white">
                            <option value="registrado" @selected($item->estado === 'registrado')>Registrado</option>
                            <option value="en_revision" @selected($item->estado === 'en_revision')>En Revisión</option>
                            <option value="resuelto" @selected($item->estado === 'resuelto')>Resuelto</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 mb-1">Respuesta Oficial de la Biblioteca</label>
                        <textarea name="respuesta_admin" rows="2" placeholder="Describe la solución o respuesta enviada al lector..."
                                  class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-xs text-white">{{ $item->respuesta_admin }}</textarea>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] text-slate-400">
                            {{ $item->respondido_at ? 'Atendido: ' . $item->respondido_at->format('d/m/Y H:i') : 'Sin respuesta aún' }}
                        </span>
                        <button type="submit" class="bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black px-4 py-2 rounded-xl">
                            Guardar Respuesta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @empty
        <div class="bg-slate-900 rounded-2xl border border-slate-800 p-8 text-center text-sm text-slate-300">
            No se encontraron reclamos ni quejas con esos filtros.
        </div>
    @endforelse
</div>

<div class="mt-4">{{ $complaints->links() }}</div>
@endsection
