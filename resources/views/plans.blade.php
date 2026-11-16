@extends('layouts.app')

@section('title', 'Planes, Club y Cómo Funciona · NOVAETHER™')

@section('content')
<div class="max-w-5xl mx-auto space-y-12">
    <div class="text-center space-y-3">
        <span class="inline-block px-3.5 py-1 rounded-full bg-amber-400/15 border border-amber-400/30 text-amber-300 text-xs font-extrabold uppercase tracking-wider">
            Transparencia Total en Soles · Sin Créditos Ocultos
        </span>
        <h1 class="font-serif-editorial text-3xl sm:text-5xl font-extrabold text-white">
            Planes de Lectura y <span class="text-amber-400">Club NOVAETHER™</span>
        </h1>
        <p class="text-sm sm:text-base text-slate-300 max-w-2xl mx-auto">
            Activa un plan al instante para alquilar en S/ 0.00 o mantente en Lector Free para pagar solo por lo que compras o reservas.
        </p>
        @if($reader)
            <p class="inline-block px-4 py-1.5 rounded-full bg-slate-900 border border-emerald-500/40 text-xs font-bold text-emerald-300">
                Tu plan actual: <strong>{{ $reader->etiquetaPlan() }}</strong>
                @if($reader->plan_vence_at)
                    (Vigente hasta {{ $reader->plan_vence_at->format('d/m/Y') }})
                @endif
            </p>
        @endif
    </div>

    <!-- Comparativa de Planes con Activación Real -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Plan Free -->
        <div class="bg-slate-900/95 border {{ ($reader?->plan_suscripcion ?? '') === 'free' ? 'border-emerald-400' : 'border-slate-800' }} rounded-3xl p-7 flex flex-col justify-between shadow-xl">
            <div class="space-y-4">
                <span class="text-xs font-black uppercase tracking-wider text-slate-300">Pago por Reserva / Compra</span>
                <h2 class="text-2xl font-extrabold text-white">Lector Free</h2>
                <div class="flex items-baseline gap-1">
                    <span class="text-4xl font-black text-white">S/ 0</span>
                    <span class="text-xs text-slate-400">/ mes</span>
                </div>
                <p class="text-xs text-slate-300 leading-relaxed">Ideal para lectores ocasionales que prefieren comprar o alquilar títulos puntuales.</p>
                <ul class="space-y-2.5 text-xs text-slate-200 pt-2">
                    <li>✓ Exploración abierta de las 12 categorías</li>
                    <li>✓ Alquiler por obra desde <strong>S/ 6.50</strong> y compra directa</li>
                    <li>✓ Ticket QR verificable y Estantería personal</li>
                    <li>✓ Canje de Puntos Nova como descuento en Carrito</li>
                </ul>
            </div>
            @auth
                @if($reader)
                    <form action="{{ route('plans.subscribe') }}" method="POST" class="mt-6">
                        @csrf
                        <input type="hidden" name="plan_suscripcion" value="free">
                        <button type="submit" @disabled($reader->plan_suscripcion === 'free')
                                class="w-full {{ $reader->plan_suscripcion === 'free' ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/40 cursor-default' : 'bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white' }} text-xs font-extrabold py-3 rounded-xl transition">
                            {{ $reader->plan_suscripcion === 'free' ? '✓ Plan Actual Activo' : 'Cambiar a Lector Free' }}
                        </button>
                    </form>
                @endif
            @else
                <a href="{{ route('register') }}" class="mt-6 block text-center bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white text-xs font-extrabold py-3 rounded-xl transition">
                    Crear Cuenta Gratis
                </a>
            @endauth
        </div>

        <!-- Club Pro -->
        <div class="bg-gradient-to-b from-slate-900 to-slate-950 border-2 border-amber-400 rounded-3xl p-7 flex flex-col justify-between shadow-2xl relative">
            <span class="absolute -top-3 right-6 bg-amber-400 text-slate-950 text-[10px] font-black uppercase px-3 py-1 rounded-full">
                Más Popular
            </span>
            <div class="space-y-4">
                <span class="text-xs font-black uppercase tracking-wider text-amber-400">Membresía Ilimitada</span>
                <h2 class="text-2xl font-extrabold text-white">Club NovaEther Pro</h2>
                <div class="flex items-baseline gap-1">
                    <span class="text-4xl font-black text-amber-400">S/ 24.90</span>
                    <span class="text-xs text-slate-300">/ mes</span>
                </div>
                <p class="text-xs text-slate-300 leading-relaxed">Para fans de manga, ficción y tecnología que leen varias obras al mes sin recargos.</p>
                <ul class="space-y-2.5 text-xs text-slate-100 pt-2">
                    <li>✓ Hasta <strong>3 libros o mangas simultáneos</strong> a <strong>S/ 0.00</strong></li>
                    <li>✓ +20 Puntos Nova al activar membresía</li>
                    <li>✓ Prioridad en Lista de Espera y Visor Digital</li>
                    <li>✓ Canje directo de Karma en compras definitivas</li>
                </ul>
            </div>
            @auth
                @if($reader)
                    <form action="{{ route('plans.subscribe') }}" method="POST" class="mt-6">
                        @csrf
                        <input type="hidden" name="plan_suscripcion" value="pro">
                        <button type="submit" @disabled($reader->plan_suscripcion === 'pro')
                                class="w-full {{ $reader->plan_suscripcion === 'pro' ? 'bg-emerald-500 text-slate-950 cursor-default' : 'bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 shadow-lg' }} text-xs font-black py-3 rounded-xl transition">
                            {{ $reader->plan_suscripcion === 'pro' ? '✓ Club Pro Activo' : 'Activar Club NovaEther Pro →' }}
                        </button>
                    </form>
                @else
                    <a href="{{ route('books.index') }}" class="mt-6 block text-center bg-gradient-to-r from-amber-400 to-orange-500 text-slate-950 text-xs font-black py-3 rounded-xl shadow-lg">Explorar Catálogo →</a>
                @endif
            @else
                <a href="{{ route('register') }}" class="mt-6 block text-center bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 text-xs font-black py-3 rounded-xl shadow-lg transition">
                    Crear Cuenta y Activar →
                </a>
            @endauth
        </div>

        <!-- Plan Estudiante -->
        <div class="bg-slate-900/95 border {{ ($reader?->plan_suscripcion ?? '') === 'estudiante' ? 'border-emerald-400' : 'border-slate-800' }} rounded-3xl p-7 flex flex-col justify-between shadow-xl">
            <div class="space-y-4">
                <span class="text-xs font-black uppercase tracking-wider text-emerald-400">Tarifa Preferencial</span>
                <h2 class="text-2xl font-extrabold text-white">Plan Estudiante</h2>
                <div class="flex items-baseline gap-1">
                    <span class="text-4xl font-black text-white">S/ 16.90</span>
                    <span class="text-xs text-slate-400">/ mes</span>
                </div>
                <p class="text-xs text-slate-300 leading-relaxed">Pensado para universitarios y desarrolladores que consultan textos STEM y literatura.</p>
                <ul class="space-y-2.5 text-xs text-slate-200 pt-2">
                    <li>✓ Alquileres incluidos en S/ 0.00 durante el mes</li>
                    <li>✓ Plazos flexibles de 7, 14 o 30 días</li>
                    <li>✓ Comprobantes QR digitales al instante</li>
                    <li>✓ +20 Puntos Nova al suscribirte</li>
                </ul>
            </div>
            @auth
                @if($reader)
                    <form action="{{ route('plans.subscribe') }}" method="POST" class="mt-6">
                        @csrf
                        <input type="hidden" name="plan_suscripcion" value="estudiante">
                        <button type="submit" @disabled($reader->plan_suscripcion === 'estudiante')
                                class="w-full {{ $reader->plan_suscripcion === 'estudiante' ? 'bg-emerald-950 text-emerald-300 border border-emerald-500/40 cursor-default' : 'bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-500/40 text-emerald-300' }} text-xs font-extrabold py-3 rounded-xl transition">
                            {{ $reader->plan_suscripcion === 'estudiante' ? '✓ Plan Estudiante Activo' : 'Activar Plan Estudiante →' }}
                        </button>
                    </form>
                @endif
            @else
                <a href="{{ route('register') }}" class="mt-6 block text-center bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-500/40 text-emerald-300 text-xs font-extrabold py-3 rounded-xl transition">
                    Solicitar Plan Estudiante
                </a>
            @endauth
        </div>
    </div>

    <!-- Cómo Funciona en 3 pasos -->
    <div class="bg-slate-900/90 border border-slate-800 rounded-3xl p-7 sm:p-10">
        <h2 class="text-xl sm:text-2xl font-extrabold text-white text-center mb-8">¿Cómo funciona NOVAETHER™ en 3 pasos?</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-center">
            <div class="bg-slate-950 rounded-2xl p-6 border border-slate-800 space-y-2">
                <div class="w-10 h-10 mx-auto rounded-xl bg-amber-400/15 text-amber-400 font-black flex items-center justify-center">1</div>
                <h3 class="font-bold text-white">Explora Stock Real</h3>
                <p class="text-xs text-slate-300 leading-relaxed">Navega entre 12 categorías con disponibilidad exacta al segundo, reseñas verificadas y visor PDF/texto.</p>
            </div>
            <div class="bg-slate-950 rounded-2xl p-6 border border-slate-800 space-y-2">
                <div class="w-10 h-10 mx-auto rounded-xl bg-amber-400/15 text-amber-400 font-black flex items-center justify-center">2</div>
                <h3 class="font-bold text-white">Compra o Alquila en Carrito</h3>
                <p class="text-xs text-slate-300 leading-relaxed">Combina compra directa y alquileres, canjea Puntos Nova y paga con Yape/Plin, tarjeta o Club.</p>
            </div>
            <div class="bg-slate-950 rounded-2xl p-6 border border-slate-800 space-y-2">
                <div class="w-10 h-10 mx-auto rounded-xl bg-amber-400/15 text-amber-400 font-black flex items-center justify-center">3</div>
                <h3 class="font-bold text-white">Recibe tu QR y Suma Karma</h3>
                <p class="text-xs text-slate-300 leading-relaxed">Presenta tu Ticket QR o sigue tu delivery. Devuelve puntual para ganar +25 Puntos Nova de descuento.</p>
            </div>
        </div>
    </div>
</div>
@endsection
