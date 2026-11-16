@extends('layouts.app')

@section('title', 'Recuperar Contraseña · NOVAETHER™')

@section('content')
<div class="max-w-md mx-auto mt-6">
    <div class="bg-slate-900/95 rounded-3xl shadow-2xl border border-slate-800 overflow-hidden p-7 space-y-5">
        <div class="text-center">
            <span class="text-3xl">🔐</span>
            <h1 class="text-xl font-extrabold text-white mt-2">Recuperar Contraseña</h1>
            <p class="text-xs text-slate-300 mt-1">Ingresa tu correo electrónico registrado y te enviaremos un enlace seguro para restablecer tu clave.</p>
        </div>

        <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Correo Electrónico</label>
                <input type="email" name="email" value="{{ old('email') }}" required placeholder="tucorreo@ejemplo.com"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-400 focus:outline-none">
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black py-3 rounded-xl text-xs shadow-lg transition">
                Enviar Enlace de Recuperación
            </button>
        </form>

        <div class="text-center pt-2 border-t border-slate-800">
            <a href="{{ route('login') }}" class="text-xs font-bold text-amber-400 hover:underline">← Volver a Iniciar Sesión</a>
        </div>
    </div>
</div>
@endsection
