@extends('layouts.app')

@section('title', 'Iniciar Sesión · NOVAETHER™')

@section('content')
@php
    $mostrarRegistroInline = old('auth_mode') === 'register'
        || $errors->hasAny(['nombres', 'apellidos', 'dni_codigo', 'telefono', 'password_confirmation'])
        || filled(old('nombres'))
        || filled(old('apellidos'))
        || request()->boolean('registro');
@endphp

<div class="max-w-lg mx-auto mt-6">
    <div class="bg-slate-900/95 rounded-3xl shadow-2xl border border-slate-800 overflow-hidden">
        <div class="bg-slate-950 border-b border-slate-800 px-6 py-8 text-center text-white">
            <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-linear-to-br from-amber-400 to-orange-500 p-0.5 shadow-xl shadow-amber-500/20">
                <div class="w-full h-full bg-[#090D16] rounded-[14px] flex items-center justify-center">
                    <svg class="w-9 h-9" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M18 8C13.5 5.5 7.5 5.5 4 7.5V27.5C7.5 25.5 13.5 25.5 18 28C22.5 25.5 28.5 25.5 32 27.5V7.5C28.5 5.5 22.5 5.5 18 8Z" stroke="#FBBF24" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18 8V28" stroke="#F59E0B" stroke-width="2.4" stroke-linecap="round"/>
                        <circle cx="18" cy="4.5" r="2" fill="#FBBF24"/>
                    </svg>
                </div>
            </div>
            <h1 id="auth-heading" class="text-2xl font-extrabold tracking-tight">
                {{ $mostrarRegistroInline ? 'Crea tu Cuenta en NOVAETHER™' : 'Bienvenido a NOVAETHER™' }}
            </h1>
            <p id="auth-subtitle" class="text-xs text-slate-300 mt-1">
                {{ $mostrarRegistroInline ? 'Regístrate gratis aquí mismo y accede al instante a tus lecturas' : 'Accede a tu cuenta personal o crea una nueva si aún no estás registrado' }}
            </p>
        </div>

        <div class="p-7">
            <!-- Formulario Iniciar Sesión -->
            <div id="inline-login-panel" class="{{ $mostrarRegistroInline ? 'hidden' : '' }}">
                <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="email" class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Correo Electrónico</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}" required autocomplete="email"
                               placeholder="tucorreo@ejemplo.com"
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-400 focus:outline-none">
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-xs font-extrabold uppercase text-slate-300">Contraseña</label>
                            <a href="{{ route('password.request') }}" class="text-[11px] font-bold text-amber-400 hover:underline">
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>
                        <input type="password" id="password" name="password" required autocomplete="current-password"
                               placeholder="••••••••"
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-400 focus:outline-none">
                    </div>

                    <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer">
                        <input type="checkbox" name="remember" value="1" class="rounded border-slate-600 bg-slate-950 text-amber-400">
                        <span>Mantener sesión iniciada en este dispositivo</span>
                    </label>

                    <button type="submit" class="w-full bg-linear-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black py-3 rounded-xl text-sm shadow-lg transition">
                        Entrar a NOVAETHER™
                    </button>
                </form>

                <div class="mt-6 pt-5 border-t border-slate-800 text-center">
                    <p class="text-xs text-slate-300 mb-2.5">¿No tienes cuenta aún?</p>
                    <button type="button" id="btn-show-inline-register"
                            class="w-full bg-slate-950 hover:bg-slate-800 border border-amber-400/50 text-amber-300 font-extrabold py-2.5 rounded-xl text-xs transition">
                        ✨ Si no tengo cuenta, crear una aquí
                    </button>
                </div>
            </div>

            <!-- Formulario Crear Cuenta Integrado en la misma pantalla -->
            <div id="inline-register-panel" class="{{ $mostrarRegistroInline ? '' : 'hidden' }}">
                @include('auth.partials.register-form')

                <div class="mt-6 pt-5 border-t border-slate-800 text-center">
                    <p class="text-xs text-slate-300 mb-2">¿Ya tienes cuenta en NOVAETHER™?</p>
                    <button type="button" id="btn-show-inline-login"
                            class="w-full bg-slate-950 hover:bg-slate-800 border border-slate-700 text-slate-200 font-extrabold py-2.5 rounded-xl text-xs transition">
                        ← Volver a Iniciar Sesión
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const loginPanel = document.getElementById('inline-login-panel');
    const registerPanel = document.getElementById('inline-register-panel');
    const heading = document.getElementById('auth-heading');
    const subtitle = document.getElementById('auth-subtitle');

    document.getElementById('btn-show-inline-register')?.addEventListener('click', () => {
        loginPanel?.classList.add('hidden');
        registerPanel?.classList.remove('hidden');
        if (heading) heading.textContent = 'Crea tu Cuenta en NOVAETHER™';
        if (subtitle) subtitle.textContent = 'Regístrate gratis aquí mismo y accede al instante a tus lecturas';
        document.getElementById('reg-nombres')?.focus();
    });

    document.getElementById('btn-show-inline-login')?.addEventListener('click', () => {
        registerPanel?.classList.add('hidden');
        loginPanel?.classList.remove('hidden');
        if (heading) heading.textContent = 'Bienvenido a NOVAETHER™';
        if (subtitle) subtitle.textContent = 'Accede a tu cuenta personal o crea una nueva si aún no estás registrado';
        document.getElementById('email')?.focus();
    });
});
</script>
@endsection
