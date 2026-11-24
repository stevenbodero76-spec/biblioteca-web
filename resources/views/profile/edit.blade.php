@extends('layouts.app')

@section('title', 'Mi Perfil y Puntos Nova · NOVAETHER™')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white">👤 Mi Perfil y Puntos Nova</h1>
            <p class="text-sm text-slate-300">Administra tus datos personales, contraseña e historial de recompensas de lectura.</p>
        </div>
        @if($reader)
            <div class="bg-slate-900 border border-amber-400/40 rounded-2xl px-4 py-2.5 text-right">
                <span class="block text-[10px] font-black uppercase text-amber-300">{{ $reader->nivelKarma() }} · {{ $reader->etiquetaSegmento() }}</span>
                <span class="text-lg font-black text-white">✨ {{ $reader->puntos_karma }} Puntos Nova</span>
                <span class="block text-[11px] text-slate-300">{{ $resumenLector['prestamos_activos'] ?? 0 }} préstamos activos · {{ $resumenLector['favoritos'] ?? 0 }} en estantería</span>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <!-- Datos personales -->
        <div class="lg:col-span-6 bg-slate-900/95 rounded-3xl border border-slate-800 p-6 space-y-4">
            <h2 class="text-base font-extrabold text-white">📝 Datos de Cuenta</h2>
            <form action="{{ route('profile.update') }}" method="POST" class="space-y-3">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Nombres *</label>
                        <input type="text" name="nombres" value="{{ old('nombres', $reader?->nombres ?? $user->name) }}" required
                               data-input-mode="letters" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s.'-]+"
                               title="Solo se permiten letras y espacios (sin números)."
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Apellidos *</label>
                        <input type="text" name="apellidos" value="{{ old('apellidos', $reader?->apellidos ?? 'NOVAETHER') }}" required
                               data-input-mode="letters" pattern="[A-Za-zÁÉÍÓÚáéíóúÑñÜü\s.'-]+"
                               title="Solo se permiten letras y espacios (sin números)."
                               class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Teléfono / WhatsApp</label>
                    <input type="tel" name="telefono" value="{{ old('telefono', $reader?->telefono) }}"
                           inputmode="numeric" data-input-mode="digits" pattern="[0-9]{7,15}" maxlength="15"
                           title="Solo números (de 7 a 15 dígitos, sin letras)."
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Correo Electrónico *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white">
                </div>
                <button type="submit" class="bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black px-5 py-2.5 rounded-xl">
                    Guardar Cambios
                </button>
            </form>
        </div>

        <!-- Seguridad / Contraseña -->
        <div class="lg:col-span-6 bg-slate-900/95 rounded-3xl border border-slate-800 p-6 space-y-4">
            <h2 class="text-base font-extrabold text-white">🔒 Cambiar Contraseña</h2>
            <form action="{{ route('profile.password') }}" method="POST" class="space-y-3">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Contraseña Actual *</label>
                    <input type="password" name="current_password" required
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Nueva Contraseña * (Mín. 6)</label>
                    <input type="password" name="password" required
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-300 mb-1">Confirmar Nueva Contraseña *</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white">
                </div>
                <button type="submit" class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white text-xs font-extrabold px-5 py-2.5 rounded-xl">
                    Actualizar Contraseña
                </button>
            </form>
        </div>
    </div>

    <!-- Historial Karma -->
    <div class="bg-slate-900/95 rounded-3xl border border-slate-800 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-extrabold text-white">✨ Movimientos de Puntos Nova (+25 pts por devolución puntual · +10 pts por reseña)</h2>
        </div>
        <div class="space-y-2">
            @forelse($movimientosKarma as $mov)
                <div class="bg-slate-950 rounded-xl border border-slate-800 px-4 py-2.5 flex items-center justify-between text-xs">
                    <div>
                        <p class="font-bold text-slate-100">{{ $mov->motivo }}</p>
                        <p class="text-[11px] text-slate-400">{{ $mov->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <span class="font-black {{ $mov->puntos >= 0 ? 'text-emerald-400' : 'text-rose-300' }}">
                        {{ $mov->puntos >= 0 ? '+' . $mov->puntos : $mov->puntos }} pts
                    </span>
                </div>
            @empty
                <p class="text-xs text-slate-400">Aún no hay movimientos registrados de Puntos Nova.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
