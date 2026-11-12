@extends('layouts.app')

@section('title', 'Iniciar Sesión · NOVAETHER™')

@section('content')
<div class="max-w-md mx-auto mt-6">
    <div class="bg-[#0E1526]/95 backdrop-blur-xl rounded-3xl shadow-2xl border border-slate-800 overflow-hidden">
        <!-- Cabecera con Logo Vectorial NOVAETHER -->
        <div class="bg-gradient-to-r from-amber-500/15 via-rose-500/15 to-cyan-500/15 border-b border-slate-800 px-6 py-8 text-center text-white">
            <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gradient-to-br from-amber-400 via-orange-500 to-rose-500 p-[2px] shadow-xl shadow-amber-500/25">
                <div class="w-full h-full bg-[#090D1A] rounded-[14px] flex items-center justify-center">
                    <svg class="w-9 h-9" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 8C13.5 5.5 7.5 5.5 4 7.5V27.5C7.5 25.5 13.5 25.5 18 28C22.5 25.5 28.5 25.5 32 27.5V7.5C28.5 5.5 22.5 5.5 18 8Z" stroke="#FBBF24" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18 8V28" stroke="#FB7185" stroke-width="2.4" stroke-linecap="round"/>
                        <circle cx="18" cy="4.5" r="2" fill="#FBBF24"/>
                    </svg>
                </div>
            </div>
            <h1 class="text-2xl font-extrabold tracking-tight">Bienvenido a NOVAETHER™</h1>
            <p class="text-xs text-slate-400 mt-1">Accede a tu cuenta de cliente o panel de administración</p>
        </div>

        <div class="p-7">
            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Correo Electrónico</label>
                    <input type="email" id="email" name="email" value="{{ old('email', 'admin@novaether.com') }}" required
                           class="w-full rounded-xl bg-slate-900/90 border border-slate-700 px-4 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Contraseña</label>
                    <input type="password" id="password" name="password" value="12345678" required
                           class="w-full rounded-xl bg-slate-900/90 border border-slate-700 px-4 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-amber-400 via-orange-500 to-rose-500 hover:from-amber-300 hover:to-rose-400 text-slate-950 font-black py-3 rounded-xl text-sm shadow-lg shadow-amber-500/20 transition">
                    Entrar a NOVAETHER™
                </button>
            </form>

            <!-- Botón destacado para Crear Cuenta si no tiene -->
            <div class="mt-5 pt-5 border-t border-slate-800 text-center">
                <p class="text-xs text-slate-400 mb-2.5">¿Eres nuevo cliente y aún no tienes cuenta?</p>
                <a href="{{ route('register') }}"
                   class="block w-full bg-slate-900 hover:bg-slate-800 border border-amber-400/50 text-amber-300 font-extrabold py-2.5 rounded-xl text-xs transition">
                    ✨ Crear Cuenta de Cliente Gratis
                </a>
            </div>

            <!-- Selección de perfil rápido -->
            <div class="mt-5 pt-4 border-t border-slate-800/80">
                <p class="text-[11px] font-extrabold text-slate-500 uppercase text-center mb-2.5">Cuentas de acceso rápido (1 clic):</p>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button"
                            onclick="document.getElementById('email').value='cliente@novaether.com'; document.getElementById('password').value='12345678';"
                            class="text-[11px] bg-cyan-950/60 hover:bg-cyan-900/70 border border-cyan-500/40 text-cyan-200 font-bold py-2 px-2 rounded-xl transition">
                        💎 Cliente<br><span class="font-normal text-[10px] opacity-75">cliente@...</span>
                    </button>
                    <button type="button"
                            onclick="document.getElementById('email').value='staff@novaether.com'; document.getElementById('password').value='12345678';"
                            class="text-[11px] bg-emerald-950/60 hover:bg-emerald-900/70 border border-emerald-500/40 text-emerald-200 font-bold py-2 px-2 rounded-xl transition">
                        ⚡ Staff<br><span class="font-normal text-[10px] opacity-75">staff@...</span>
                    </button>
                    <button type="button"
                            onclick="document.getElementById('email').value='admin@novaether.com'; document.getElementById('password').value='12345678';"
                            class="text-[11px] bg-amber-950/60 hover:bg-amber-900/70 border border-amber-500/40 text-amber-200 font-bold py-2 px-2 rounded-xl transition">
                        👑 Admin<br><span class="font-normal text-[10px] opacity-75">admin@...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
