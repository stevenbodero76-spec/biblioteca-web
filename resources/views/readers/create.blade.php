@extends('layouts.app')

@section('title', 'Registrar Cliente · NOVAETHER™')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-white">➕ Registrar Nuevo Cliente / Lector</h1>
            <p class="text-sm text-slate-300">Crea la ficha comercial y su cuenta de acceso web sincronizada automáticamente.</p>
        </div>
        <a href="{{ route('readers.index') }}" class="text-xs font-extrabold uppercase text-amber-400 hover:underline">← Volver</a>
    </div>

    <form action="{{ route('readers.store') }}" method="POST" class="bg-slate-900/95 rounded-3xl border border-slate-800 p-6 sm:p-8 shadow-2xl space-y-4">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">DNI / Código de Carnet *</label>
                <input type="text" name="dni_codigo" value="{{ old('dni_codigo') }}" required placeholder="Ej. 74829103"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-400 focus:border-amber-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Teléfono</label>
                <input type="text" name="telefono" value="{{ old('telefono') }}" placeholder="Ej. 987654321"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-400 focus:border-amber-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Nombres *</label>
                <input type="text" name="nombres" value="{{ old('nombres') }}" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Apellidos *</label>
                <input type="text" name="apellidos" value="{{ old('apellidos') }}" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Correo Electrónico *</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Contraseña Acceso Web (Opcional)</label>
                <input type="password" name="password" placeholder="Si lo dejas vacío se genera temporal"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Plan Suscripción *</label>
                <select name="plan_suscripcion" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
                    <option value="free" @selected(old('plan_suscripcion', 'free') === 'free')>Lector Free (Pago por operación)</option>
                    <option value="estudiante" @selected(old('plan_suscripcion') === 'estudiante')>Plan Estudiante (S/ 16.90)</option>
                    <option value="pro" @selected(old('plan_suscripcion') === 'pro')>Club NovaEther Pro (S/ 24.90)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Segmento *</label>
                <select name="tipo" required class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
                    <option value="publico" @selected(old('tipo', 'publico') === 'publico')>Público General</option>
                    <option value="estudiante" @selected(old('tipo') === 'estudiante')>Estudiante</option>
                    <option value="docente" @selected(old('tipo') === 'docente')>Docente / Tech</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Vencimiento del Plan</label>
                <input type="date" name="plan_vence_at" value="{{ old('plan_vence_at') }}"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Estado *</label>
                <select name="estado" required class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
                    <option value="activo" @selected(old('estado', 'activo') === 'activo')>Activo</option>
                    <option value="suspendido" @selected(old('estado') === 'suspendido')>Suspendido</option>
                </select>
            </div>
        </div>

        <div class="pt-3 flex justify-end gap-2">
            <a href="{{ route('readers.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-700 text-xs font-bold text-slate-200 hover:bg-slate-800">Cancelar</a>
            <button type="submit" class="bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 text-xs font-black px-6 py-2.5 rounded-xl shadow-lg">
                💾 Guardar Cliente
            </button>
        </div>
    </form>
</div>
@endsection
