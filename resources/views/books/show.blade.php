@extends('layouts.app')

@section('title', $book->titulo . ' · NOVAETHER™')

@section('meta')
    <meta property="og:type" content="book">
    <meta property="og:title" content="{{ $book->titulo }} — {{ $book->autor }} | NOVAETHER™">
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit($book->sinopsis ?? 'Disponible en NOVAETHER™ Store & Digital Vault.', 150) }}">
    @if($book->portada_url)
        <meta property="og:image" content="{{ $book->portada_url }}">
    @endif
@endsection

@section('content')

<div class="mb-5 flex items-center justify-between">
    <a href="{{ route('books.index') }}" class="inline-flex items-center gap-2 text-xs font-extrabold uppercase tracking-wider text-amber-400 hover:text-amber-300 transition">
        ← Volver al Catálogo Global
    </a>

    <div class="flex items-center gap-2">
        @auth
            <form action="{{ route('favorites.toggle', $book) }}" method="POST">
                @csrf
                <button type="submit" class="bg-slate-900 hover:bg-slate-800 border border-slate-700 text-xs font-bold px-3.5 py-2 rounded-xl transition {{ $esFavorito ? 'text-rose-400 border-rose-500/40' : 'text-slate-200' }}">
                    {{ $esFavorito ? '❤️ En Mi Estantería' : '🤍 Guardar en Estantería' }}
                </button>
            </form>

            @if(auth()->user()->esAdmin())
                <a href="{{ route('books.edit', $book) }}" class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white text-xs font-bold px-4 py-2 rounded-xl transition">
                    ✏️ Editar Obra / Stock
                </a>
            @endif
        @endauth
    </div>
</div>

<!-- FICHA PRINCIPAL -->
<div class="bg-slate-900/95 rounded-3xl border border-slate-800 shadow-2xl overflow-hidden mb-8">
    <div class="grid grid-cols-1 lg:grid-cols-12">
        <!-- Portada -->
        <div class="lg:col-span-4 bg-gradient-to-br from-slate-950 to-slate-900 p-8 flex flex-col items-center justify-center text-white border-b lg:border-b-0 lg:border-r border-slate-800">
            <div class="w-52 sm:w-60 aspect-[2/3] rounded-2xl overflow-hidden shadow-2xl border-2 border-amber-400/30 bg-slate-900 flex items-center justify-center">
                @if($book->portada_url)
                    <img src="{{ $book->portada_url }}" alt="Portada de {{ $book->titulo }}" class="w-full h-full object-cover"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="hidden w-full h-full items-center justify-center text-6xl bg-slate-900">📚</div>
                @else
                    <span class="text-6xl">📚</span>
                @endif
            </div>

            <div class="mt-5 text-center space-y-2">
                <span class="inline-block px-3.5 py-1 rounded-full text-xs font-black bg-amber-400 text-slate-950">
                    {{ $book->category->icono }} {{ $book->category->nombre }}
                </span>
                <p class="text-xs text-slate-300 font-mono">ISBN: {{ $book->isbn }}</p>
                <span class="inline-block px-2.5 py-0.5 rounded-md text-[10px] font-bold bg-emerald-500/15 text-emerald-300 border border-emerald-500/30">
                    🛡️ Sello Mint Condition · Stock Verificado
                </span>
            </div>
        </div>

        <!-- Información y Acciones -->
        <div class="lg:col-span-8 p-6 sm:p-10 flex flex-col justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    @if($book->estaDisponible())
                        <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                            🟢 EN STOCK ({{ $book->stock_disponible }} de {{ $book->stock_total }} unidades disponibles)
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs font-black bg-rose-500/20 text-rose-200 border border-rose-500/40">
                            🔴 AGOTADO TEMPORALMENTE (0 de {{ $book->stock_total }} disponibles)
                        </span>
                    @endif

                    <span class="px-3 py-1 rounded-full text-xs font-extrabold uppercase bg-slate-800 text-slate-200 border border-slate-700">
                        Formato: {{ $book->formato }}
                    </span>

                    @if($book->reviews_count > 0)
                        <span class="px-3 py-1 rounded-full text-xs font-black bg-amber-400/15 text-amber-300 border border-amber-400/30">
                            ★ {{ number_format((float) $book->reviews_avg_rating, 1) }} ({{ $book->reviews_count }} {{ $book->reviews_count === 1 ? 'reseña' : 'reseñas' }})
                        </span>
                    @endif

                    @if($waitlistCount > 0)
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-800 text-amber-300 border border-amber-500/30">
                            🔔 {{ $waitlistCount }} persona(s) en cola
                        </span>
                    @endif
                </div>

                <h1 class="font-serif-editorial text-2xl sm:text-4xl font-extrabold text-white leading-tight">
                    {{ $book->titulo }}
                </h1>
                <p class="text-base sm:text-lg font-bold text-slate-300 mt-1">
                    ✍️ Autor: <span class="text-amber-300">{{ $book->autor }}</span>
                </p>

                @if(! $book->estaDisponible() && $proximaDevolucion)
                    <div class="mt-3 rounded-xl bg-amber-950/40 border border-amber-500/30 px-4 py-2.5 text-xs text-amber-200">
                        ⏳ <strong>Retorno estimado de ejemplar:</strong> {{ \Carbon\Carbon::parse($proximaDevolucion)->format('d/m/Y') }}.
                        @if($miPosicionCola)
                            Tu posición actual en cola es <strong>#{{ $miPosicionCola }}</strong>.
                        @endif
                    </div>
                @endif

                <div class="mt-4 bg-slate-950/90 rounded-2xl p-4 border border-slate-800">
                    <h2 class="text-xs font-extrabold uppercase tracking-wider text-amber-400 mb-1">Sinopsis Oficial</h2>
                    <p class="text-sm text-slate-200 leading-relaxed">
                        {{ $book->sinopsis ?? 'Obra disponible en el catálogo oficial de NOVAETHER™.' }}
                    </p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
                    <div class="bg-slate-950/90 p-3 rounded-xl border border-slate-800">
                        <span class="block text-[10px] font-extrabold uppercase text-slate-400">Editorial</span>
                        <span class="text-xs font-bold text-slate-100">{{ $book->editorial ?? 'General' }}</span>
                    </div>
                    <div class="bg-slate-950/90 p-3 rounded-xl border border-slate-800">
                        <span class="block text-[10px] font-extrabold uppercase text-slate-400">Año</span>
                        <span class="text-xs font-bold text-slate-100">{{ $book->anio_publicacion ?? 'Clásico' }}</span>
                    </div>
                    <div class="bg-slate-950/90 p-3 rounded-xl border border-slate-800">
                        <span class="block text-[10px] font-extrabold uppercase text-slate-400">Ubicación</span>
                        <span class="text-xs font-bold text-slate-100">{{ $book->ubicacion_estante ?? 'Catálogo General' }}</span>
                    </div>
                    <div class="bg-slate-950/90 p-3 rounded-xl border border-slate-800">
                        <span class="block text-[10px] font-extrabold uppercase text-slate-400">Disponibilidad</span>
                        <span class="text-xs font-black {{ $book->estaDisponible() ? 'text-emerald-400' : 'text-rose-400' }}">
                            {{ $book->stock_disponible }} / {{ $book->stock_total }} Uds.
                        </span>
                    </div>
                </div>
            </div>

            <!-- Lectura y préstamo como acciones principales -->
            <div class="mt-6 pt-5 border-t border-slate-800 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    @if($book->archivo_pdf)
                        <a href="#visor-virtual"
                           class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black text-sm px-5 py-3 rounded-xl shadow-lg shadow-amber-500/20 transition">
                            📖 Leer Obra Completa Ahora
                        </a>
                    @else
                        <a href="#visor-virtual"
                           class="inline-flex items-center justify-center gap-2 bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black text-sm px-5 py-3 rounded-xl shadow-lg shadow-amber-500/20 transition">
                            📖 Ver Muestra de Lectura
                        </a>
                    @endif

                    @if($book->estaDisponible())
                        <a href="{{ route('loans.create', ['book_id' => $book->id]) }}"
                           class="inline-flex items-center justify-center gap-2 bg-emerald-500/15 hover:bg-emerald-500/25 border border-emerald-400/50 text-emerald-200 font-extrabold text-sm px-5 py-3 rounded-xl transition">
                            📚 Solicitar Préstamo Físico Gratis
                        </a>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2">
                @if($book->estaDisponible())
                    @auth
                        <form action="{{ route('cart.add', $book) }}" method="POST" class="js-cart-add-form">
                            @csrf
                            <input type="hidden" name="tipo_operacion" value="venta">
                            <button type="submit" data-no-loading="true"
                                    class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-100 font-bold text-xs px-4 py-2.5 rounded-xl transition">
                                🛒 Comprar
                            </button>
                        </form>

                        <form action="{{ route('cart.add', $book) }}" method="POST" class="js-cart-add-form">
                            @csrf
                            <input type="hidden" name="tipo_operacion" value="alquiler">
                            <input type="hidden" name="duracion_dias" value="7">
                            <button type="submit" data-no-loading="true"
                                    class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-100 font-bold text-xs px-4 py-2.5 rounded-xl transition">
                                + Añadir alquiler al carrito
                            </button>
                        </form>
                    @endauth
                @else
                    @auth
                        @if(auth()->user()->reader)
                            @if($miWaitlist)
                                <form action="{{ route('waitlists.destroy', $miWaitlist) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="bg-emerald-950 hover:bg-rose-950 text-emerald-200 hover:text-rose-200 border border-emerald-500/50 font-extrabold text-sm px-5 py-3 rounded-xl transition">
                                        ✅ Turno #{{ $miPosicionCola ?? 1 }} en Cola (Salir)
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('waitlists.store', $book) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-sm px-6 py-3 rounded-xl shadow-lg transition">
                                        🔔 Unirme a Lista de Espera ({{ $waitlistCount }} delante)
                                    </button>
                                </form>
                            @endif
                        @endif
                    @else
                        <a href="{{ route('login') }}"
                           class="bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-sm px-6 py-3 rounded-xl shadow transition">
                            🔔 Inicia sesión para Lista de Espera
                        </a>
                    @endauth
                @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- VISOR DE LECTURA -->
<div id="visor-virtual" class="scroll-mt-24 rounded-3xl border border-slate-800 bg-slate-950 text-slate-100 p-6 sm:p-8 shadow-2xl mb-10">
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-5 border-b border-slate-800">
        <div class="flex items-center gap-3">
            <span class="text-2xl">🖥️</span>
            <div>
                <h2 class="text-base font-extrabold text-amber-400">Lector NOVAETHER™</h2>
                <p class="text-xs text-slate-300">
                    @if($book->archivo_pdf)
                        Lectura digital de «{{ $book->titulo }}»
                    @else
                        No hay enlace de lectura completa para esta obra; aquí puedes consultar una muestra.
                    @endif
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-xs">
            @if($book->esPdfEmbeddable())
                <button type="button" onclick="alternarModoLector('pdf')" id="btn-modo-pdf" class="px-3 py-1.5 rounded-lg bg-amber-400 text-slate-950 font-black">📄 Visor PDF Real</button>
                <button type="button" onclick="alternarModoLector('texto')" id="btn-modo-texto" class="px-3 py-1.5 rounded-lg bg-slate-800 text-white font-bold border border-slate-700">📝 Modo Texto</button>
                <span class="h-4 w-[1px] bg-slate-700 mx-1"></span>
            @endif
            <button type="button" onclick="cambiarTamanoFuente(-1)" class="px-2.5 py-1.5 rounded-lg bg-slate-800 text-white font-bold border border-slate-700">A-</button>
            <button type="button" onclick="cambiarTamanoFuente(1)" class="px-2.5 py-1.5 rounded-lg bg-slate-800 text-white font-bold border border-slate-700">A+</button>
            <button type="button" onclick="cambiarTemaVisor('oscuro')" class="px-3 py-1.5 rounded-lg bg-slate-800 text-white font-bold border border-slate-700">🌙</button>
            <button type="button" onclick="cambiarTemaVisor('sepia')" class="px-3 py-1.5 rounded-lg bg-[#F4ECD8] text-amber-950 font-bold">📜</button>
            <button type="button" onclick="cambiarTemaVisor('claro')" class="px-3 py-1.5 rounded-lg bg-white text-slate-900 font-bold">☀️</button>
        </div>
    </div>

    @if($book->esPdfEmbeddable())
        <div id="lector-pdf-panel" class="mt-4">
            <div class="rounded-2xl overflow-hidden border border-slate-800 bg-slate-900">
                <iframe src="{{ $book->archivo_pdf }}" title="Obra completa: {{ $book->titulo }}" class="w-full h-[480px] sm:h-[600px]"></iframe>
            </div>
            <div class="mt-2 flex justify-end">
                <a href="{{ $book->archivo_pdf }}" target="_blank" rel="noopener noreferrer" class="text-xs font-bold text-amber-400 hover:underline">
                    Abrir PDF en pantalla completa ↗
                </a>
            </div>
        </div>
    @elseif($book->archivo_pdf)
        <div class="mt-4 flex items-center justify-between bg-slate-900 border border-slate-800 rounded-xl px-4 py-2.5 text-xs">
            <span class="text-slate-300">🔗 Edición digital externa disponible para referencia oficial</span>
            <a href="{{ $book->archivo_pdf }}" target="_blank" rel="noopener noreferrer" class="font-bold text-amber-400 hover:underline">Abrir enlace externo ↗</a>
        </div>
    @endif

    <div id="lector-texto-panel" class="{{ $book->esPdfEmbeddable() ? 'hidden' : '' }}">
        <div class="mt-4 max-w-3xl mx-auto">
            <div class="flex items-center justify-between text-[11px] text-slate-300 mb-1">
                <span>Progreso de lectura previa</span>
                <span id="progreso-label" class="font-bold text-amber-400">Página 1 de 2 (50%)</span>
            </div>
            <div class="w-full h-1.5 bg-slate-800 rounded-full overflow-hidden">
                <div id="progreso-barra" class="h-full bg-amber-400 transition-all duration-300" style="width: 50%"></div>
            </div>
        </div>

        <div id="hoja-lectura" class="mt-4 max-w-3xl mx-auto rounded-2xl bg-slate-900 border border-slate-800 p-6 sm:p-10 leading-relaxed transition-colors duration-300 text-base">
            <p class="text-xs uppercase tracking-widest text-amber-400 font-bold mb-2">{{ $book->category->nombre }} · Muestra Editorial</p>
            <h3 class="font-serif-editorial text-2xl font-bold mb-1">{{ $book->titulo }}</h3>
            <p class="text-xs opacity-80 mb-6">Por {{ $book->autor }} · {{ $book->editorial }} ({{ $book->anio_publicacion }})</p>

            <div id="pagina-1" class="space-y-4 leading-loose">
                <p class="font-bold text-amber-400 text-sm uppercase">Fragmento 01 — Introducción</p>
                <p>{{ $book->sinopsis }}</p>
                <p>
                    Esta edición forma parte del catálogo verificado de <strong>NOVAETHER™</strong>.
                    @if($book->estaDisponible())
                        Contamos con <strong>{{ $book->stock_disponible }} unidad(es) disponible(s)</strong> en <em>{{ $book->ubicacion_estante }}</em> para compra directa o alquiler inmediato.
                    @else
                        Todas las unidades ({{ $book->stock_total }}) están actualmente reservadas. Únete a la cola de espera para recibir prioridad al liberarse una copia.
                    @endif
                </p>
            </div>

            <div id="pagina-2" class="hidden space-y-4 leading-loose">
                <p class="font-bold text-amber-400 text-sm uppercase">Fragmento 02 — Garantía &amp; Beneficios Nova</p>
                <p>
                    Cada ejemplar físico de <strong>NOVAETHER™</strong> pasa por control <em>Mint Condition</em>. Al devolver tus alquileres puntualmente acumulas <strong>+25 Puntos Nova</strong> canjeables como descuento directo en tu carrito.
                </p>
            </div>

            <div class="mt-8 pt-4 border-t border-slate-700/40 flex items-center justify-between">
                <button type="button" onclick="irPagina(1)" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold">← Fragmento 1</button>
                <button type="button" onclick="irPagina(2)" class="px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black">Fragmento 2 →</button>
            </div>
        </div>
    </div>
</div>

<!-- RED DE OBRAS INTERCONECTADAS -->
@if($relacionados->isNotEmpty())
    @php
        $networkTotal = $relacionados->count();
        $networkFirstPosition = 125;
        $networkLastPosition = $networkFirstPosition + (250 * ($networkTotal - 1));
        $networkRailStart = min(500, $networkFirstPosition);
        $networkRailEnd = max(500, $networkLastPosition);
    @endphp
    <section class="mb-10 rounded-3xl border border-slate-800 bg-slate-900/90 p-6 sm:p-8 shadow-2xl" aria-labelledby="related-network-title">
        <div class="mb-6 text-center">
            <h2 id="related-network-title" class="text-xl font-extrabold text-white">🕸️ Red de Obras Interconectadas</h2>
            <p class="mt-1 text-xs text-slate-300">Explora títulos relacionados de la misma categoría.</p>
        </div>

        <div class="mx-auto max-w-sm rounded-2xl border border-amber-400/50 bg-slate-950 px-4 py-3 text-center shadow-lg shadow-amber-500/10">
            <span class="text-[10px] font-black uppercase tracking-wider text-amber-300">Obra actual</span>
            <p class="mt-1 text-sm font-extrabold text-white">{{ $book->titulo }}</p>
            <p class="text-xs text-slate-300">{{ $book->autor }}</p>
        </div>

        <div class="relative mx-auto hidden h-14 max-w-5xl lg:block" aria-hidden="true">
            <svg viewBox="0 0 1000 60" preserveAspectRatio="none" class="h-full w-full">
                <g fill="none" stroke="rgba(251,191,36,0.55)" stroke-width="2">
                    <line x1="500" y1="0" x2="500" y2="20" />
                    <line x1="{{ $networkRailStart }}" y1="20" x2="{{ $networkRailEnd }}" y2="20" />
                    @foreach($relacionados as $rel)
                        @php $networkPosition = $networkFirstPosition + (250 * ($loop->iteration - 1)); @endphp
                        <line x1="{{ $networkPosition }}" y1="20" x2="{{ $networkPosition }}" y2="60" />
                    @endforeach
                </g>
            </svg>
        </div>
        <div class="mx-auto h-7 w-px bg-amber-400/50 lg:hidden" aria-hidden="true"></div>

        <div class="relative ml-5 grid grid-cols-1 gap-3 border-l border-amber-400/30 pl-5 sm:ml-0 sm:grid-cols-2 sm:gap-4 sm:border-0 sm:pl-0 lg:grid-cols-4">
            @foreach($relacionados as $rel)
                <a href="{{ route('books.show', $rel) }}" class="relative flex gap-3 rounded-2xl border border-slate-800 bg-slate-950 p-4 shadow-lg transition hover:border-amber-400/60">
                    <span class="absolute -left-[1.32rem] top-7 w-5 border-t border-amber-400/40 sm:hidden" aria-hidden="true"></span>
                    <span class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-full border border-amber-400/40 bg-amber-400/10 text-xs font-black text-amber-300 sm:hidden" aria-hidden="true">{{ $loop->iteration }}</span>
                    <div class="h-24 w-16 flex-shrink-0 overflow-hidden rounded-lg bg-slate-900">
                        @if($rel->portada_url)
                            <img src="{{ $rel->portada_url }}" alt="Portada de {{ $rel->titulo }}" class="h-full w-full object-cover">
                        @else
                            <div class="flex h-full items-center justify-center text-2xl">📚</div>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h3 class="line-clamp-2 text-sm font-bold text-white">{{ $rel->titulo }}</h3>
                        <p class="mt-0.5 truncate text-xs text-slate-300">{{ $rel->autor }}</p>
                        <span class="mt-2 inline-block rounded px-2 py-0.5 text-[10px] font-black {{ $rel->estaDisponible() ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-200' }}">
                            {{ $rel->estaDisponible() ? $rel->stock_disponible . ' disp.' : 'Agotado' }}
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </section>
@endif

<!-- COMUNIDAD Y RESEÑAS VERIFICADAS -->
<div class="bg-slate-900/95 rounded-3xl border border-slate-800 p-6 sm:p-8 shadow-2xl mb-10">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
            <h2 class="text-xl font-extrabold text-white">💬 Comunidad &amp; Reseñas Verificadas ({{ $book->reviews_count }})</h2>
            <p class="text-xs text-slate-300 mt-0.5">Opiniones reales de lectores sobre la edición, estado del ejemplar y trama.</p>
        </div>
        @if($book->reviews_count > 0)
            <div class="bg-slate-950 border border-amber-400/30 px-4 py-2 rounded-2xl text-center">
                <span class="text-lg font-black text-amber-400">★ {{ number_format((float) $book->reviews_avg_rating, 1) }}</span>
                <span class="text-xs text-slate-300">/ 5.0</span>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-7 space-y-3">
            @forelse($book->reviews as $review)
                <div class="bg-slate-950 rounded-2xl border {{ $review->visible ? 'border-slate-800' : 'border-rose-500/40' }} p-4 space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="text-xs font-extrabold text-white">{{ $review->user->name }}</span>
                            @if($review->es_spoiler)
                                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 border border-rose-500/40">⚠️ Spoiler</span>
                            @endif
                            @if(! $review->visible)
                                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-rose-600 text-white">Oculta</span>
                            @endif
                        </div>
                        <span class="text-xs font-black text-amber-400">
                            {{ str_repeat('★', $review->rating) }}{{ str_repeat('☆', 5 - $review->rating) }}
                        </span>
                    </div>

                    @if($review->es_spoiler)
                        <div>
                            <p id="spoiler-text-{{ $review->id }}" class="hidden text-sm text-slate-200 leading-relaxed">{{ $review->comentario }}</p>
                            <button type="button"
                                    onclick="document.getElementById('spoiler-text-{{ $review->id }}').classList.toggle('hidden'); this.textContent = this.textContent.includes('Ver') ? '🙈 Ocultar spoiler' : '👁️ Ver comentario con spoiler';"
                                    class="text-xs font-bold text-amber-400 hover:underline">
                                👁️ Ver comentario con spoiler
                            </button>
                        </div>
                    @else
                        <p class="text-sm text-slate-200 leading-relaxed">{{ $review->comentario }}</p>
                    @endif

                    <div class="flex items-center justify-between text-[11px] text-slate-400">
                        <span>{{ $review->created_at->diffForHumans() }}</span>

                        @auth
                            @if(auth()->user()->esAdmin())
                                <div class="flex items-center gap-2">
                                    <form action="{{ route('reviews.visibility', $review) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-amber-300 hover:underline font-bold">
                                            {{ $review->visible ? 'Ocultar' : 'Mostrar' }}
                                        </button>
                                    </form>
                                    <form action="{{ route('reviews.destroy', $review) }}" method="POST" data-confirm="¿Eliminar reseña de {{ $review->user->name }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-400 hover:underline font-bold">Eliminar</button>
                                    </form>
                                </div>
                            @endif
                        @endauth
                    </div>
                </div>
            @empty
                <div class="bg-slate-950 rounded-2xl border border-slate-800 p-6 text-center text-sm text-slate-300">
                    Aún no hay reseñas para esta obra. ¡Sé el primero en compartir tu opinión y gana +10 Puntos Nova!
                </div>
            @endforelse
        </div>

        <div class="lg:col-span-5">
            @auth
                <form action="{{ route('reviews.store', $book) }}" method="POST" class="bg-slate-950 rounded-2xl border border-slate-800 p-5 space-y-4">
                    @csrf
                    <h3 class="text-sm font-extrabold text-amber-300">
                        {{ $miResena ? '✏️ Actualizar tu reseña' : '⭐ Dejar una reseña (+10 Puntos Nova)' }}
                    </h3>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Calificación *</label>
                        <select name="rating" required class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-sm text-white">
                            @for($i = 5; $i >= 1; $i--)
                                <option value="{{ $i }}" @selected(old('rating', $miResena?->rating ?? 5) == $i)>
                                    {{ str_repeat('★', $i) }} ({{ $i }} estrellas)
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Comentario *</label>
                        <textarea name="comentario" rows="3" required minlength="5" placeholder="¿Qué te pareció la historia, edición o calidad de lectura?"
                                  class="w-full rounded-xl bg-slate-900 border border-slate-700 px-3 py-2 text-sm text-white placeholder-slate-400">{{ old('comentario', $miResena?->comentario) }}</textarea>
                    </div>

                    <label class="flex items-center gap-2 text-xs text-slate-200 cursor-pointer">
                        <input type="checkbox" name="es_spoiler" value="1" @checked(old('es_spoiler', $miResena?->es_spoiler))
                               class="rounded border-slate-600 bg-slate-900 text-amber-400">
                        <span>⚠️ Este comentario contiene spoilers de la trama</span>
                    </label>

                    <button type="submit" class="w-full bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black py-2.5 rounded-xl transition">
                        Publicar Reseña
                    </button>
                </form>
            @else
                <div class="bg-slate-950 rounded-2xl border border-slate-800 p-6 text-center space-y-3">
                    <p class="text-sm font-bold text-white">¿Ya leíste esta obra?</p>
                    <p class="text-xs text-slate-300">Inicia sesión para dejar tu calificación y ganar +10 Puntos Nova.</p>
                    <a href="{{ route('login') }}" class="inline-block bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black px-5 py-2.5 rounded-xl">
                        Iniciar Sesión
                    </a>
                </div>
            @endauth
        </div>
    </div>
</div>

<script>
let tamanoFuente = 16;
const storageKey = 'novaether_preview_page_{{ $book->id }}';

function alternarModoLector(modo) {
    const pdfPanel = document.getElementById('lector-pdf-panel');
    const txtPanel = document.getElementById('lector-texto-panel');
    const btnPdf = document.getElementById('btn-modo-pdf');
    const btnTxt = document.getElementById('btn-modo-texto');
    if (!pdfPanel || !txtPanel) return;

    if (modo === 'pdf') {
        pdfPanel.classList.remove('hidden');
        txtPanel.classList.add('hidden');
        btnPdf.className = 'px-3 py-1.5 rounded-lg bg-amber-400 text-slate-950 font-black';
        btnTxt.className = 'px-3 py-1.5 rounded-lg bg-slate-800 text-white font-bold border border-slate-700';
    } else {
        pdfPanel.classList.add('hidden');
        txtPanel.classList.remove('hidden');
        btnTxt.className = 'px-3 py-1.5 rounded-lg bg-amber-400 text-slate-950 font-black';
        btnPdf.className = 'px-3 py-1.5 rounded-lg bg-slate-800 text-white font-bold border border-slate-700';
    }
}

function cambiarTamanoFuente(delta) {
    tamanoFuente = Math.min(22, Math.max(14, tamanoFuente + delta * 2));
    const hoja = document.getElementById('hoja-lectura');
    if (hoja) hoja.style.fontSize = tamanoFuente + 'px';
}

function cambiarTemaVisor(tema) {
    const hoja = document.getElementById('hoja-lectura');
    if (!hoja) return;
    if (tema === 'sepia') {
        hoja.className = 'mt-4 max-w-3xl mx-auto rounded-2xl bg-[#F4ECD8] text-amber-950 border border-amber-300 p-6 sm:p-10 leading-relaxed transition-colors duration-300';
    } else if (tema === 'claro') {
        hoja.className = 'mt-4 max-w-3xl mx-auto rounded-2xl bg-white text-slate-900 border border-slate-200 p-6 sm:p-10 leading-relaxed transition-colors duration-300';
    } else {
        hoja.className = 'mt-4 max-w-3xl mx-auto rounded-2xl bg-slate-900 text-slate-100 border border-slate-800 p-6 sm:p-10 leading-relaxed transition-colors duration-300';
    }
}

function irPagina(num) {
    const p1 = document.getElementById('pagina-1');
    const p2 = document.getElementById('pagina-2');
    const barra = document.getElementById('progreso-barra');
    const label = document.getElementById('progreso-label');
    if (!p1 || !p2) return;

    if (num === 2) {
        p1.classList.add('hidden');
        p2.classList.remove('hidden');
        barra.style.width = '100%';
        label.textContent = 'Página 2 de 2 (100%)';
    } else {
        p2.classList.add('hidden');
        p1.classList.remove('hidden');
        barra.style.width = '50%';
        label.textContent = 'Página 1 de 2 (50%)';
    }
    try { localStorage.setItem(storageKey, String(num)); } catch (e) {}
}

document.addEventListener('DOMContentLoaded', () => {
    try {
        const saved = localStorage.getItem(storageKey);
        if (saved === '2') irPagina(2);
    } catch (e) {}
});
</script>

@endsection
