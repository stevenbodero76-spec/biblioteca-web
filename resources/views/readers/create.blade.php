@extends('layouts.app')

@section('title', 'Registrar Lector')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-5">
        <h1 class="text-2xl font-bold text-slate-900">➕ Registrar Nuevo Lector</h1>
        <a href="{{ route('readers.index') }}" class="text-sm font-semibold text-slate-600 hover:underline">← Volver</a>
    </div>

    <form action="{{ route('readers.store') }}" method="POST" class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">DNI / Código de Carnet *</label>
                <input type="text" name="dni_codigo" value="{{ old('dni_codigo') }}" required placeholder="Ej. 74829103"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Teléfono</label>
                <input type="text" name="telefono" value="{{ old('telefono') }}" placeholder="Ej. 987654321"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Nombres *</label>
                <input type="text" name="nombres" value="{{ old('nombres') }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Apellidos *</label>
                <input type="text" name="apellidos" value="{{ old('apellidos') }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Correo Electrónico *</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Tipo de Lector *</label>
                <select name="tipo" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="estudiante" @selected(old('tipo', 'estudiante') === 'estudiante')>Estudiante</option>
                    <option value="docente" @selected(old('tipo') === 'docente')>Docente</option>
                    <option value="publico" @selected(old('tipo') === 'publico')>Público Externo</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Estado *</label>
                <select name="estado" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="activo" @selected(old('estado', 'activo') === 'activo')>Activo</option>
                    <option value="suspendido" @selected(old('estado') === 'suspendido')>Suspendido</option>
                </select>
            </div>
        </div>

        <div class="pt-3 flex justify-end gap-2">
            <a href="{{ route('readers.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700">Cancelar</a>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2 rounded-lg shadow">
                Guardar Lector
            </button>
        </div>
    </form>
</div>
@endsection

