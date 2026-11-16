@extends('layouts.app')

@section('title', 'Nueva Contraseña · NOVAETHER™')

@section('content')
<div class="max-w-md mx-auto mt-6">
    <div class="bg-slate-900/95 rounded-3xl shadow-2xl border border-slate-800 overflow-hidden p-7 space-y-5">
        <div class="text-center">
            <h1 class="text-xl font-extrabold text-white">🔑 Restablecer Contraseña</h1>
            <p class="text-xs text-slate-300 mt-1">Define tu nueva contraseña segura de acceso.</p>
        </div>

        <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Correo Electrónico</label>
                <input type="email" name="email" value="{{ old('email', $email) }}" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Nueva Contraseña * (Mín. 6)</label>
                <input type="password" name="password" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Confirmar Nueva Contraseña *</label>
                <input type="password" name="password_confirmation" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black py-3 rounded-xl text-xs shadow-lg transition">
                Guardar Nueva Contraseña
            </button>
        </form>
    </div>
</div>
@endsection
