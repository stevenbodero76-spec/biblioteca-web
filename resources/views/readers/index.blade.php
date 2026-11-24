@extends('layouts.app')

@section('title', 'Clientes y Miembros · NOVAETHER™')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-white">👥 Directorio de Lectores</h1>
        <p class="text-sm text-slate-300">Lectores registrados, su categoría y préstamos activos.</p>
    </div>
    <a href="{{ route('readers.create') }}" class="bg-linear-to-r from-amber-400 to-orange-500 text-slate-950 text-xs font-black px-4 py-2.5 rounded-xl shadow transition">
        ➕ Registrar Lector con Acceso
    </a>
</div>

<form method="GET" action="{{ route('readers.index') }}" class="bg-slate-900/95 rounded-2xl border border-slate-800 p-4 mb-6 shadow-xl">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3">
        <div class="lg:col-span-5">
            <label class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1">Buscar lector</label>
            <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="DNI, nombres o correo..."
                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white placeholder-slate-400">
        </div>
        <div class="lg:col-span-3">
            <label class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1">Categoría de lector</label>
            <select name="tipo" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                <option value="">Todos</option>
                <option value="publico" @selected(($filtros['tipo'] ?? '') === 'publico')>Lector Público</option>
                <option value="estudiante" @selected(($filtros['tipo'] ?? '') === 'estudiante')>Estudiante</option>
                <option value="docente" @selected(($filtros['tipo'] ?? '') === 'docente')>Docente</option>
            </select>
        </div>
        <div class="lg:col-span-2">
            <label class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1">Estado</label>
            <select name="estado" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                <option value="">Todos</option>
                <option value="activo" @selected(($filtros['estado'] ?? '') === 'activo')>🟢 Activo</option>
                <option value="suspendido" @selected(($filtros['estado'] ?? '') === 'suspendido')>🔴 Suspendido</option>
            </select>
        </div>
        <div class="lg:col-span-2 flex items-end gap-2">
            <button type="submit" class="flex-1 bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black py-2.5 px-3 rounded-xl">
                🔍 Buscar
            </button>
            <a href="{{ route('readers.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold py-2.5 px-3 rounded-xl">
                Limpiar
            </a>
        </div>
    </div>
</form>

<div class="bg-slate-900/95 rounded-2xl border border-slate-800 shadow-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-950 border-b border-slate-800 text-xs uppercase text-slate-300">
                    <th class="py-3.5 px-4">Documento</th>
                    <th class="py-3.5 px-4">Lector / Categoría</th>
                    <th class="py-3.5 px-4">Contacto y Cuenta</th>
                    <th class="py-3.5 px-4">Préstamos activos</th>
                    <th class="py-3.5 px-4">Estado</th>
                    <th class="py-3.5 px-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/80">
                @forelse($readers as $reader)
                    <tr class="hover:bg-slate-800/40">
                        <td class="py-3.5 px-4 font-mono font-bold text-amber-300">{{ $reader->dni_codigo ?: 'Sin doc.' }}</td>
                        <td class="py-3.5 px-4">
                            <p class="font-bold text-white">{{ $reader->nombre_completo }}</p>
                            <span class="text-[11px] text-amber-300 font-semibold">{{ $reader->etiquetaSegmento() }}</span>
                        </td>
                        <td class="py-3.5 px-4 text-xs text-slate-300">
                            <div>{{ $reader->email }}</div>
                            <div class="text-slate-400">{{ $reader->telefono ?? 'Sin teléfono' }}</div>
                            <span class="inline-block mt-0.5 text-[10px] font-bold {{ $reader->user_id ? 'text-emerald-300' : 'text-rose-300' }}">
                                {{ $reader->user_id ? '✓ Cuenta web vinculada' : '⚠️ Sin usuario web' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-xs">
                            <span class="font-bold text-slate-100">{{ $reader->prestamos_activos_count }} préstamo(s)</span>
                        </td>
                        <td class="py-3.5 px-4">
                            @if($reader->estado === 'activo')
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300">Activo</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500/20 text-rose-200">Suspendido</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right space-x-2">
                            <a href="{{ route('readers.edit', $reader) }}" class="text-xs font-bold text-amber-400 hover:underline">✏️ Editar</a>
                            @if(auth()->user()->esAdmin())
                                <form action="{{ route('readers.destroy', $reader) }}" method="POST" class="inline"
                                      data-confirm="¿Eliminar permanentemente al cliente {{ $reader->nombre_completo }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-bold text-rose-400 hover:underline">🗑️ Eliminar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-300">No hay clientes registrados con esos criterios.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $readers->links() }}
</div>
@endsection
