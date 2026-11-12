@extends('layouts.app')

@section('title', 'Clientes y Miembros · NOVAETHER™')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-white">👥 Directorio de Clientes y Miembros</h1>
        <p class="text-sm text-slate-400">Gestión de miembros registrados (incluye cuentas creadas desde la tienda online).</p>
    </div>
    <a href="{{ route('readers.create') }}" class="bg-gradient-to-r from-amber-400 to-orange-500 text-slate-950 text-xs font-black px-4 py-2.5 rounded-xl shadow transition">
        ➕ Registrar Cliente Manualmente
    </a>
</div>

<form method="GET" action="{{ route('readers.index') }}" class="bg-[#0E1526] rounded-2xl border border-slate-800 p-4 mb-6 shadow-xl">
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        <div class="sm:col-span-2">
            <label class="block text-[11px] font-extrabold uppercase text-slate-400 mb-1">Buscar cliente (DNI, nombres o correo)</label>
            <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Ej. 72849102, Carlos, @gmail.com..."
                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white placeholder-slate-500">
        </div>
        <div>
            <label class="block text-[11px] font-extrabold uppercase text-slate-400 mb-1">Estado</label>
            <select name="estado" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                <option value="">Todos</option>
                <option value="activo" @selected(($filtros['estado'] ?? '') === 'activo')>🟢 Activo</option>
                <option value="suspendido" @selected(($filtros['estado'] ?? '') === 'suspendido')>🔴 Suspendido</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black py-2.5 px-3 rounded-xl">
                🔍 Buscar
            </button>
            <a href="{{ route('readers.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold py-2.5 px-3 rounded-xl">
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
                    <th class="py-3.5 px-4">Documento / ID</th>
                    <th class="py-3.5 px-4">Cliente / Miembro</th>
                    <th class="py-3.5 px-4">Contacto</th>
                    <th class="py-3.5 px-4">Reservas</th>
                    <th class="py-3.5 px-4">Estado</th>
                    <th class="py-3.5 px-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/80">
                @forelse($readers as $reader)
                    <tr class="hover:bg-slate-900/60">
                        <td class="py-3.5 px-4 font-mono font-bold text-amber-300">{{ $reader->dni_codigo }}</td>
                        <td class="py-3.5 px-4 font-bold text-white">{{ $reader->nombre_completo }}</td>
                        <td class="py-3.5 px-4 text-xs text-slate-400">
                            <div>{{ $reader->email }}</div>
                            <div>{{ $reader->telefono ?? 'Sin teléfono' }}</div>
                        </td>
                        <td class="py-3.5 px-4 text-xs">
                            <span class="font-bold text-slate-200">{{ $reader->prestamos_activos_count }} activa(s)</span>
                            <span class="text-slate-500">/ {{ $reader->loans_count }} total</span>
                        </td>
                        <td class="py-3.5 px-4">
                            @if($reader->estado === 'activo')
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300">Activo</span>
                            @else
                                <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500/20 text-rose-300">Suspendido</span>
                            @endif
                        </td>
                        <td class="py-3.5 px-4 text-right space-x-2">
                            <a href="{{ route('readers.edit', $reader) }}" class="text-xs font-bold text-amber-400 hover:underline">✏️ Editar</a>
                            @if(auth()->user()->esAdmin())
                                <form action="{{ route('readers.destroy', $reader) }}" method="POST" class="inline"
                                      onsubmit="return confirm('¿Eliminar este cliente?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-bold text-rose-400 hover:underline">🗑️ Eliminar</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-500">No hay clientes registrados con esos criterios.</td>
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
