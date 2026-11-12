@extends('layouts.app')

@section('title', 'Crear Cuenta Gratis · NOVAETHER™')

@section('content')
<div class="max-w-xl mx-auto mt-4">
    <div class="bg-[#0E1526]/95 backdrop-blur-xl rounded-3xl shadow-2xl border border-slate-800 overflow-hidden">
        <!-- Cabecera con Logo Vectorial NOVAETHER -->
        <div class="bg-gradient-to-r from-amber-500/15 via-rose-500/15 to-cyan-500/15 border-b border-slate-800 px-6 py-7 text-center">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-400/15 border border-amber-400/30 text-amber-300 text-[11px] font-extrabold uppercase tracking-wider mb-3">
                ✨ Membresía Digital Instantánea
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">Crea tu Cuenta en NOVAETHER™</h1>
            <p class="text-xs text-slate-400 mt-1">Regístrate gratis para reservar libros físicos, cómics, mangas y activar el visor Cloud.</p>
        </div>

        <div class="p-6 sm:p-8">
            <form action="{{ route('register.post') }}" method="POST" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Nombres *</label>
                        <input type="text" name="nombres" value="{{ old('nombres') }}" required placeholder="Ej. Mateo Sebastián"
                               class="w-full rounded-xl bg-slate-900/90 border border-slate-700 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Apellidos *</label>
                        <input type="text" name="apellidos" value="{{ old('apellidos') }}" required placeholder="Ej. Salazar Vega"
                               class="w-full rounded-xl bg-slate-900/90 border border-slate-700 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">DNI / Documento Identidad *</label>
                        <input type="text" name="dni_codigo" value="{{ old('dni_codigo') }}" required placeholder="Ej. 75849302"
                               class="w-full rounded-xl bg-slate-900/90 border border-slate-700 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Teléfono / WhatsApp</label>
                        <input type="text" name="telefono" value="{{ old('telefono') }}" placeholder="Ej. 987654321"
                               class="w-full rounded-xl bg-slate-900/90 border border-slate-700 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-400 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Correo Electrónico *</label>
                    <input type="email" name="email" value="{{ old('email') }}" required placeholder="tucorreo@gmail.com"
                           class="w-full rounded-xl bg-slate-900/90 border border-slate-700 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-400 focus:outline-none">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Contraseña * (Mín. 6 caracteres)</label>
                        <input type="password" name="password" required placeholder="••••••••"
                               class="w-full rounded-xl bg-slate-900/90 border border-slate-700 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-400 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Confirmar Contraseña *</label>
                        <input type="password" name="password_confirmation" required placeholder="••••••••"
                               class="w-full rounded-xl bg-slate-900/90 border border-slate-700 px-4 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-400 focus:outline-none">
                    </div>
                </div>

                <button type="submit"
                        class="w-full bg-gradient-to-r from-amber-400 via-orange-500 to-rose-500 hover:from-amber-300 hover:to-rose-400 text-slate-950 font-black py-3.5 rounded-xl text-sm shadow-lg shadow-amber-500/20 transition mt-2">
                    🚀 Crear mi Cuenta y Entrar Ahora
                </button>
            </form>

            <div class="mt-6 pt-5 border-t border-slate-800 text-center">
                <p class="text-xs text-slate-400">
                    ¿Ya tienes una cuenta en NOVAETHER™?
                    <a href="{{ route('login') }}" class="text-amber-400 font-extrabold hover:underline ml-1">Inicia Sesión aquí →</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

