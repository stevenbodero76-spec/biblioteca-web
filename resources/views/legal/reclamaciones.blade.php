@extends('layouts.app')

@section('title', 'Libro de Reclamaciones Virtual · NOVAETHER™')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <span class="text-[11px] font-black uppercase tracking-widest text-amber-400">Atención al Lector · Perú</span>
            <h1 class="text-2xl font-extrabold text-white mt-0.5">📖 Libro de Reclamaciones Virtual</h1>
            <p class="text-xs text-slate-300 mt-1">Conforme al Código de Protección y Defensa del Consumidor (Ley N° 29571).</p>
        </div>
        <a href="{{ route('home') }}" class="text-xs font-extrabold uppercase text-amber-400 hover:underline">← Volver</a>
    </div>

    <form action="{{ route('complaints.store') }}" method="POST" class="bg-slate-900/95 rounded-3xl border border-slate-800 p-6 sm:p-8 shadow-2xl space-y-4">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Nombre Completo del Consumidor *</label>
                <input type="text" name="nombre_completo" value="{{ old('nombre_completo', auth()->user()?->name) }}" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">DNI / Carné de Extranjería *</label>
                <input type="text" name="dni_ce" value="{{ old('dni_ce', auth()->user()?->reader?->dni_codigo) }}" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Teléfono</label>
                <input type="text" name="telefono" value="{{ old('telefono', auth()->user()?->reader?->telefono) }}"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Correo Electrónico *</label>
                <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Tipo de Registro *</label>
                <select name="tipo" required class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
                    <option value="reclamo" @selected(old('tipo') === 'reclamo')>Reclamo (Disconformidad con el producto o servicio)</option>
                    <option value="queja" @selected(old('tipo') === 'queja')>Queja (Malestar respecto a la atención)</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Detalle de los Hechos *</label>
            <textarea name="detalle" rows="3" required placeholder="Describe con claridad lo ocurrido..."
                      class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-400">{{ old('detalle') }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Pedido del Consumidor *</label>
            <textarea name="pedido_consumidor" rows="2" required placeholder="Indica qué solución solicitas..."
                      class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-400">{{ old('pedido_consumidor') }}</textarea>
        </div>

        <button type="submit" class="w-full bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 text-xs font-black py-3 rounded-xl shadow-lg">
            📩 Enviar Hoja de Reclamación Oficial
        </button>
    </form>
</div>
@endsection
