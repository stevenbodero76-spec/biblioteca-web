@extends('layouts.app')

@section('title', 'Crear Cuenta Gratis · NOVAETHER™')

@section('content')
<div class="max-w-xl mx-auto mt-4">
    <div class="bg-slate-900/95 rounded-3xl shadow-2xl border border-slate-800 overflow-hidden">
        <div class="bg-slate-950 border-b border-slate-800 px-6 py-7 text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-400/15 border border-amber-400/30 text-amber-300 text-[11px] font-extrabold uppercase tracking-wider mb-3">
                ✨ Acceso Digital Gratuito Instantáneo
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Crea tu Cuenta en NOVAETHER™</h1>
            <p class="text-xs text-slate-300 mt-1">Regístrate gratis para leer obras completas, reservar libros y generar tickets QR.</p>
        </div>

        <div class="p-6 sm:p-8">
            @include('auth.partials.register-form')

            <div class="mt-6 pt-5 border-t border-slate-800 text-center">
                <p class="text-xs text-slate-300">
                    ¿Ya tienes una cuenta en NOVAETHER™?
                    <a href="{{ route('login') }}" class="text-amber-400 font-extrabold hover:underline ml-1">Inicia Sesión aquí →</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
