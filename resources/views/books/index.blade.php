@extends('layouts.app')

@section('title', 'Catálogo Global · NOVAETHER™ Books, Manga & Digital Vault')

@section('content')

<!-- HERO EDITORIAL -->
<div class="relative rounded-3xl bg-linear-to-br from-slate-900 via-[#0C1220] to-slate-950 text-white overflow-hidden shadow-2xl border border-slate-800 mb-8">
    <div class="absolute -top-28 -right-24 w-96 h-96 rounded-full bg-amber-500/15 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-28 -left-24 w-96 h-96 rounded-full bg-indigo-500/15 blur-3xl pointer-events-none"></div>

    <div class="relative p-6 sm:p-10 lg:p-12">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-7 space-y-5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-amber-400/15 border border-amber-400/40 text-amber-300 text-xs font-extrabold uppercase tracking-wider">
                        Lectura abierta · Manga · Clásicos · Tech
                    </span>
                    @guest
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-slate-800 border border-slate-700 text-slate-200 hover:border-amber-400 hover:text-amber-300 text-xs font-extrabold transition">
                            ¿Nuevo aquí? Crea tu cuenta gratis →
                        </a>
                    @endguest
                </div>

                <h1 class="font-serif-editorial text-3xl sm:text-5xl font-extrabold tracking-tight leading-[1.12]">
                    Descubre y abre tu próxima lectura en <span class="text-amber-400">NOVAETHER™</span>
                </h1>

                <p class="text-slate-300 text-sm sm:text-base max-w-2xl leading-relaxed">
                    Explora el catálogo, filtra por idioma y licencia, y accede a bibliotecas digitales abiertas desde cada obra. La disponibilidad depende de cada fuente.
                </p>

                <!-- Buscador rápido sincronizado con el filtro en vivo -->
                <form method="GET" action="{{ route('books.index') }}" class="pt-1" onsubmit="event.preventDefault(); document.getElementById('live-search-input').value = this.buscar.value; document.getElementById('live-search-input').dispatchEvent(new Event('input')); document.getElementById('catalogo-resultados').scrollIntoView({behavior:'smooth'});">
                    <div class="flex flex-col sm:flex-row gap-2 bg-slate-950/90 p-2 rounded-2xl border border-slate-700 focus-within:border-amber-400 transition shadow-inner">
                        <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}"
                               placeholder="Busca al instante por título, autor, ISBN, One Piece, Batman, Dune..."
                               class="flex-1 bg-transparent px-4 py-2.5 text-sm text-white placeholder-slate-400 focus:outline-none">
                        <button type="submit"
                                class="bg-linear-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black text-sm px-6 py-3 rounded-xl transition shadow-lg">
                            Explorar lecturas
                        </button>
                    </div>
                </form>

                <!-- Píldoras de tendencias -->
                <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
                    <span class="text-slate-300 font-bold">Explora rápido:</span>
                    <button type="button" data-quick-cat="{{ $categories->firstWhere('nombre', 'Cómics, Manga y Novela Gráfica')?->id }}"
                            class="js-cat-chip px-3.5 py-1.5 rounded-full bg-slate-900 border border-slate-700 hover:border-amber-400 text-amber-300 font-bold transition">
                        🎌 Manga &amp; Cómics
                    </button>
                    <button type="button" data-quick-cat="{{ $categories->firstWhere('nombre', 'Ciencia Ficción, Fantasía y Terror')?->id }}"
                            class="js-cat-chip px-3.5 py-1.5 rounded-full bg-slate-900 border border-slate-700 hover:border-amber-400 text-slate-200 font-bold transition">
                        🚀 Sci-Fi &amp; Fantasía
                    </button>
                    <button type="button" data-quick-cat="{{ $categories->firstWhere('nombre', 'Tecnología, IA y Desarrollo (Tech)')?->id }}"
                            class="js-cat-chip px-3.5 py-1.5 rounded-full bg-slate-900 border border-slate-700 hover:border-amber-400 text-slate-200 font-bold transition">
                        💻 Código &amp; IA
                    </button>
                    <button type="button" onclick="aplicarFiltroStock('agotado')"
                            class="px-3.5 py-1.5 rounded-full bg-rose-950/80 border border-rose-500/40 hover:bg-rose-600 text-rose-200 font-bold transition">
                        🔔 Lista de Espera
                    </button>
                </div>
            </div>

            <!-- Panel de métricas -->
            <div class="lg:col-span-5 grid grid-cols-2 lg:grid-cols-3 gap-3.5">
                <div class="bg-slate-950/75 border border-slate-800 hover:border-amber-400/40 p-5 rounded-2xl transition">
                    <div class="flex items-center justify-between">
                        <span class="text-2xl">📚</span>
                        <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-amber-400/15 text-amber-300">Catálogo</span>
                    </div>
                    <p class="text-3xl font-black text-white mt-2">{{ $stats['total_titulos'] }}</p>
                    <p class="text-xs text-slate-300 mt-0.5">Obras y ediciones selectas</p>
                </div>

                <div class="bg-slate-950/75 border border-slate-800 hover:border-emerald-400/40 p-5 rounded-2xl transition">
                    <div class="flex items-center justify-between">
                        <span class="text-2xl">⚡</span>
                        <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-emerald-400/15 text-emerald-300">Stock Real</span>
                    </div>
                    <p class="text-3xl font-black text-emerald-400 mt-2">{{ $stats['disponibles_ahora'] }}</p>
                    <p class="text-xs text-slate-300 mt-0.5">Unidades listas para entrega</p>
                </div>

                <div class="bg-linear-to-br from-amber-500/20 to-orange-500/10 border border-amber-400/40 p-5 rounded-2xl flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-black uppercase text-amber-300">Karma Lector</span>
                        <p class="text-sm font-extrabold text-white mt-1">+25 pts por devolución puntual</p>
                    </div>
                    @guest
                        <a href="{{ route('register') }}" class="mt-3 inline-block text-center bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black py-2 px-3 rounded-xl transition">
                            Unirme Gratis →
                        </a>
                    @else
                        <a href="{{ route('loans.index') }}" class="mt-3 inline-block text-center bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-400/40 text-emerald-200 text-xs font-bold py-1.5 px-3 rounded-xl transition">
                            Ver Mis Préstamos →
                        </a>
                    @endguest
                </div>
            </div>
        </div>
    </div>
</div>

<!-- VITRINA DINÁMICA CON PESTAÑAS INTERACTIVAS -->
@if(empty($filtros['buscar']) && empty($filtros['category_id']) && empty($filtros['formato']) && empty($filtros['idioma']) && empty($filtros['licencia']) && empty($filtros['disponibilidad']))
<div class="mb-10 bg-slate-900/90 rounded-3xl p-6 sm:p-8 text-white shadow-2xl border border-slate-800">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <span class="text-xs font-black uppercase tracking-widest text-amber-400">Vitrina Destacada en Vivo</span>
            <h2 class="text-2xl sm:text-3xl font-extrabold mt-0.5">Colecciones Recomendadas</h2>
        </div>

        <!-- Pestañas dinámicas sin recargar -->
        <div class="inline-flex flex-wrap gap-1.5 bg-slate-950 p-1.5 rounded-2xl border border-slate-800">
            <button type="button" data-tab="valorados"
                    class="js-showcase-tab px-4 py-2 rounded-xl text-xs font-black transition bg-amber-400 text-slate-950">
                🔥 Mejor Valorados
            </button>
            <button type="button" data-tab="manga"
                    class="js-showcase-tab px-4 py-2 rounded-xl text-xs font-bold transition text-slate-300 hover:text-white">
                🎌 Manga &amp; Cómics
            </button>
            <button type="button" data-tab="tech"
                    class="js-showcase-tab px-4 py-2 rounded-xl text-xs font-bold transition text-slate-300 hover:text-white">
                💻 Tech &amp; Negocios
            </button>
        </div>
    </div>

    @foreach(['valorados' => $showcaseTabs['valorados'], 'manga' => $showcaseTabs['manga'], 'tech' => $showcaseTabs['tech']] as $tabKey => $items)
        <div id="showcase-panel-{{ $tabKey }}" class="js-showcase-panel grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 {{ $tabKey !== 'valorados' ? 'hidden' : '' }}">
            @foreach($items as $item)
                <a href="{{ route('books.show', $item) }}"
                   class="group bg-slate-950/80 hover:bg-slate-950 border border-slate-800 hover:border-amber-400/70 rounded-2xl p-3.5 flex flex-col justify-between transition duration-300 shadow-lg">
                    <div>
                        <div class="aspect-2/3 w-full rounded-xl overflow-hidden bg-slate-900 mb-3 relative shadow-md">
                            @if($item->portada_url)
                                <img src="{{ $item->portada_url }}" alt="Portada de {{ $item->titulo }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="hidden w-full h-full items-center justify-center text-4xl bg-slate-900">📚</div>
                            @else
                                <div class="w-full h-full flex items-center justify-center text-4xl">📚</div>
                            @endif
                            <span class="absolute top-2 right-2 text-[10px] font-black px-2.5 py-0.5 rounded-full shadow {{ $item->estaDisponible() ? 'bg-emerald-400 text-slate-950' : 'bg-rose-600 text-white' }}">
                                {{ $item->estaDisponible() ? $item->stock_disponible . ' DISP.' : 'AGOTADO' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1 text-[11px] font-bold mb-1">
                            @if(($item->reviews_count ?? 0) > 0)
                                <span class="text-amber-400">★ {{ number_format((float) $item->reviews_avg_rating, 1) }}</span>
                                <span class="text-slate-300">({{ $item->reviews_count }})</span>
                            @else
                                <span class="text-slate-300">✨ Estreno</span>
                            @endif
                        </div>
                        <h3 class="font-bold text-xs sm:text-sm text-white group-hover:text-amber-300 line-clamp-2 leading-snug">{{ $item->titulo }}</h3>
                        <p class="text-[11px] text-slate-300 mt-1 truncate">{{ $item->autor }}</p>
                    </div>
                    <div class="mt-3 pt-2.5 border-t border-slate-800 flex items-center justify-between text-[11px] text-slate-300 font-extrabold">
                        <span>Ver detalle</span>
                        <span class="group-hover:translate-x-1 transition">→</span>
                    </div>
                </a>
            @endforeach
        </div>
    @endforeach
</div>
@endif

<!-- NAVEGADOR COMPACTO EN 1 FILA + BOTÓN EXPANDIR LAS 12 -->
@php
    $primerasCategorias = $categories->take(4);
    $categoriasExtra = $categories->slice(4);
    $extraActiva = $categoriasExtra->contains('id', (int) ($filtros['category_id'] ?? 0));
@endphp

<div id="catalogo-resultados" class="mb-5">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-xs font-black uppercase tracking-widest text-amber-400">Explorar Catálogo por Categoría</h2>
        @auth
            @if(auth()->user()->esAdmin())
                <a href="{{ route('books.create') }}" class="bg-linear-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 text-xs font-black px-4 py-2 rounded-xl shadow transition">
                    ➕ Publicar Nuevo Libro / Cómic
                </a>
            @endif
        @endauth
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <button type="button" data-quick-cat=""
                class="js-cat-chip px-3.5 py-2 rounded-xl text-xs border transition {{ empty($filtros['category_id']) ? 'bg-amber-400 text-slate-950 border-amber-400 shadow font-extrabold' : 'bg-slate-900 text-slate-200 border-slate-800 hover:border-amber-400/50 hover:text-white font-bold' }}">
            Todas ({{ $stats['total_titulos'] }})
        </button>

        @foreach($primerasCategorias as $cat)
            <button type="button" data-quick-cat="{{ $cat->id }}"
                    class="js-cat-chip px-3.5 py-2 rounded-xl text-xs border transition inline-flex items-center gap-1.5 {{ ($filtros['category_id'] ?? '') == $cat->id ? 'bg-amber-400 text-slate-950 border-amber-400 shadow font-extrabold' : 'bg-slate-900 text-slate-200 border-slate-800 hover:border-amber-400/50 hover:text-white font-bold' }}">
                <span>{{ $cat->icono }}</span>
                <span>{{ $cat->nombre }}</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ ($filtros['category_id'] ?? '') == $cat->id ? 'bg-slate-950 text-amber-300' : 'bg-slate-800 text-slate-200' }}">{{ $cat->books_count }}</span>
            </button>
        @endforeach

        @if($categoriasExtra->isNotEmpty())
            <button type="button" id="toggle-extra-cats"
                    class="px-3.5 py-2 rounded-xl text-xs font-extrabold border border-slate-700 bg-slate-950 text-amber-300 hover:border-amber-400 transition">
                {{ $extraActiva ? '− Ocultar categorías' : '+ Ver todas (' . $categories->count() . ')' }}
            </button>
        @endif
    </div>

    <!-- Categorías extra (colapsadas por defecto para mantener 1 sola fila limpia) -->
    @if($categoriasExtra->isNotEmpty())
        <div id="extra-categories" class="{{ $extraActiva ? 'flex' : 'hidden' }} flex-wrap gap-2 mt-2">
            @foreach($categoriasExtra as $cat)
                <button type="button" data-quick-cat="{{ $cat->id }}"
                        class="js-cat-chip px-3.5 py-2 rounded-xl text-xs border transition inline-flex items-center gap-1.5 {{ ($filtros['category_id'] ?? '') == $cat->id ? 'bg-amber-400 text-slate-950 border-amber-400 shadow font-extrabold' : 'bg-slate-900 text-slate-200 border-slate-800 hover:border-amber-400/50 hover:text-white font-bold' }}">
                    <span>{{ $cat->icono }}</span>
                    <span>{{ $cat->nombre }}</span>
                    <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ ($filtros['category_id'] ?? '') == $cat->id ? 'bg-slate-950 text-amber-300' : 'bg-slate-800 text-slate-200' }}">{{ $cat->books_count }}</span>
                </button>
            @endforeach
        </div>
    @endif
</div>

<!-- BOTÓN TOGGLE FILTROS EN MÓVIL (Mejora UX / WebView) -->
<div class="lg:hidden mb-3 flex items-center justify-between gap-2">
    <button type="button" id="toggle-mobile-filters"
            class="w-full bg-slate-900 hover:bg-slate-800 border border-slate-700 text-amber-300 text-xs font-extrabold py-2.5 px-4 rounded-xl flex items-center justify-between shadow">
        <span>⚙️ Filtrar, buscar y ordenar catálogo</span>
        <span>▾</span>
    </button>
</div>

<!-- BARRA DE FILTROS EN VIVO Y ORDENAMIENTO DINÁMICO -->
<form id="live-filter-form" method="GET" action="{{ route('books.index') }}" class="hidden lg:block bg-slate-900/95 rounded-2xl border border-slate-800 p-4 mb-6 shadow-xl">
    <input type="hidden" id="live-category-input" name="category_id" value="{{ $filtros['category_id'] ?? '' }}">

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
        <div class="lg:col-span-4">
            <label for="live-search-input" class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1.5">Buscar en vivo</label>
            <input type="text" id="live-search-input" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Título, autor, ISBN o editorial..."
                   class="w-full h-10 rounded-xl bg-slate-950 border border-slate-700 px-3.5 text-sm text-white placeholder-slate-400 focus:border-amber-400 focus:outline-none">
        </div>

        <div class="lg:col-span-2">
            <label for="live-orden-select" class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1.5">Ordenar por</label>
            <div class="relative">
                <select id="live-orden-select" name="orden" class="appearance-none w-full h-10 rounded-xl bg-slate-950 border border-slate-700 pl-3 pr-8 text-xs font-semibold text-white focus:border-amber-400 focus:outline-none">
                    <option value="">Más recientes</option>
                    <option value="mejor_valorados" @selected(($filtros['orden'] ?? '') === 'mejor_valorados')>Mejor valorados</option>
                    <option value="mayor_stock" @selected(($filtros['orden'] ?? '') === 'mayor_stock')>Mayor disponibilidad</option>
                </select>
                <svg class="w-4 h-4 text-slate-400 pointer-events-none absolute right-2.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
            </div>
        </div>

        <div class="lg:col-span-2">
            <label for="live-idioma-select" class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1.5">Idioma</label>
            <div class="relative">
                <select id="live-idioma-select" name="idioma" class="appearance-none w-full h-10 rounded-xl bg-slate-950 border border-slate-700 pl-3 pr-8 text-xs font-semibold text-white focus:border-amber-400 focus:outline-none">
                    <option value="">Todos los idiomas</option>
                    <option value="es" @selected(($filtros['idioma'] ?? '') === 'es')>🇪🇸 Español</option>
                    <option value="en" @selected(($filtros['idioma'] ?? '') === 'en')>🇬🇧 Inglés</option>
                </select>
                <svg class="w-4 h-4 text-slate-400 pointer-events-none absolute right-2.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
            </div>
        </div>

        <div class="lg:col-span-2">
            <label for="live-licencia-select" class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1.5">Licencia</label>
            <div class="relative">
                <select id="live-licencia-select" name="licencia" class="appearance-none w-full h-10 rounded-xl bg-slate-950 border border-slate-700 pl-3 pr-8 text-xs font-semibold text-white focus:border-amber-400 focus:outline-none">
                    <option value="">Todas las licencias</option>
                    <option value="dominio_publico" @selected(($filtros['licencia'] ?? '') === 'dominio_publico')>Dominio Público</option>
                    <option value="acceso_abierto" @selected(($filtros['licencia'] ?? '') === 'acceso_abierto')>Acceso Abierto</option>
                </select>
                <svg class="w-4 h-4 text-slate-400 pointer-events-none absolute right-2.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
            </div>
        </div>

        <div class="lg:col-span-2">
            <label for="live-formato-select" class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1.5">Formato</label>
            <div class="relative">
                <select id="live-formato-select" name="formato" class="appearance-none w-full h-10 rounded-xl bg-slate-950 border border-slate-700 pl-3 pr-8 text-xs font-semibold text-white focus:border-amber-400 focus:outline-none">
                    <option value="">Todos los formatos</option>
                    <option value="ambos" @selected(($filtros['formato'] ?? '') === 'ambos')>Físico + Digital</option>
                    <option value="digital" @selected(($filtros['formato'] ?? '') === 'digital')>Solo Digital</option>
                    <option value="fisico" @selected(($filtros['formato'] ?? '') === 'fisico')>Solo Físico</option>
                </select>
                <svg class="w-4 h-4 text-slate-400 pointer-events-none absolute right-2.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
            </div>
        </div>

        <div class="lg:col-span-2">
            <label for="live-stock-select" class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1.5">Disponibilidad</label>
            <div class="relative">
                <select id="live-stock-select" name="disponibilidad" class="appearance-none w-full h-10 rounded-xl bg-slate-950 border border-slate-700 pl-3 pr-8 text-xs font-semibold text-white focus:border-amber-400 focus:outline-none">
                    <option value="">Todo el stock</option>
                    <option value="disponible" @selected(($filtros['disponibilidad'] ?? '') === 'disponible')>Con stock disponible</option>
                    <option value="agotado" @selected(($filtros['disponibilidad'] ?? '') === 'agotado')>Agotados (Lista espera)</option>
                </select>
                <svg class="w-4 h-4 text-slate-400 pointer-events-none absolute right-2.5 top-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
            </div>
        </div>

        <div class="lg:col-span-2 flex items-center gap-2">
            <button type="submit" class="flex-1 h-10 inline-flex items-center justify-center gap-1.5 bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black px-3 rounded-xl transition shadow">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 105.64 5.64a7.5 7.5 0 0010.61 10.61z" />
                </svg>
                <span>Filtrar</span>
            </button>
            <button type="button" id="live-reset-btn" class="h-10 inline-flex items-center justify-center bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold px-3 rounded-xl transition">
                Limpiar
            </button>
        </div>
    </div>
</form>

<!-- CONTENEDOR DINÁMICO DE RESULTADOS (Se actualiza vía AJAX sin recargar) -->
<div id="books-grid-container" class="transition-opacity duration-200">
    @include('books.partials.grid', ['books' => $books, 'favoriteBookIds' => $favoriteBookIds, 'filtros' => $filtros])
</div>

@endsection
