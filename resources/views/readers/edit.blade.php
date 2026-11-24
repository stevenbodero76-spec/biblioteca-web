@extends('layouts.app')

@section('title', 'Editar Lector · NOVAETHER™')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-white">✏️ Editar Lector</h1>
            <p class="text-sm text-slate-300">{{ $reader->nombre_completo }} · Cuenta web sincronizada</p>
        </div>
        <a href="{{ route('readers.index') }}" class="text-xs font-extrabold uppercase text-amber-400 hover:underline">← Volver</a>
    </div>

    <form action="{{ route('readers.update', $reader) }}" method="POST" class="bg-slate-900/95 rounded-3xl border border-slate-800 p-6 sm:p-8 shadow-2xl space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">DNI / Código de Carnet (Opcional)</label>
                <input type="text" name="dni_codigo" value="{{ old('dni_codigo', $reader->dni_codigo) }}" placeholder="Opcional"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Teléfono</label>
                <input type="text" name="telefono" value="{{ old('telefono', $reader->telefono) }}"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Nombres *</label>
                <input type="text" name="nombres" value="{{ old('nombres', $reader->nombres) }}" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Apellidos *</label>
                <input type="text" name="apellidos" value="{{ old('apellidos', $reader->apellidos) }}" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Correo Electrónico *</label>
                <input type="email" name="email" value="{{ old('email', $reader->email) }}" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Nueva Contraseña (Opcional)</label>
                <input type="password" name="password" placeholder="Dejar vacío para conservar actual"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-500">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Categoría de lector *</label>
                <select name="tipo" required class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
                    <option value="publico" @selected(old('tipo', $reader->tipo) === 'publico')>Lector Público</option>
                    <option value="estudiante" @selected(old('tipo', $reader->tipo) === 'estudiante')>Estudiante</option>
                    <option value="docente" @selected(old('tipo', $reader->tipo) === 'docente')>Docente</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Estado *</label>
                <select name="estado" required class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
                    <option value="activo" @selected(old('estado', $reader->estado) === 'activo')>Activo</option>
                    <option value="suspendido" @selected(old('estado', $reader->estado) === 'suspendido')>Suspendido (Bloquea acceso web)</option>
                </select>
            </div>
        </div>

        <div class="pt-3 flex justify-end gap-2">
            <a href="{{ route('readers.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-700 text-xs font-bold text-slate-200 hover:bg-slate-800">Cancelar</a>
            <button type="submit" class="bg-linear-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 text-xs font-black px-6 py-2.5 rounded-xl shadow-lg">
                💾 Guardar Cambios
            </button>
        </div>
    </form>
</div>
@endsection
