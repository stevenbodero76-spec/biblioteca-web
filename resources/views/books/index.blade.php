@extends('layouts.app')

@section('title', 'Catálogo Global · NOVAETHER™ Books, Manga & Cloud')

@section('content')

<!-- HERO CINEMATOGRÁFICO UNIFICADO -->
<div class="relative rounded-3xl bg-gradient-to-br from-[#0D1326] via-[#090D1A] to-[#140D26] text-white overflow-hidden shadow-2xl border border-slate-800/90 mb-8">
    <div class="absolute -top-28 -right-24 w-96 h-96 rounded-full bg-amber-500/20 blur-3xl pointer-events-none"></div>
    <div class="absolute -bottom-28 -left-24 w-96 h-96 rounded-full bg-rose-500/15 blur-3xl pointer-events-none"></div>

    <div class="relative p-6 sm:p-10 lg:p-12">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
            <div class="lg:col-span-7 space-y-5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-amber-400/15 border border-amber-400/40 text-amber-300 text-xs font-extrabold uppercase tracking-wider">
                        🔥 Bestsellers · Manga · Cómics · Tech
                    </span>
                    @guest
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-1.5 px-3.5 py-1 rounded-full bg-rose-500/20 border border-rose-400/40 text-rose-200 hover:bg-rose-500 hover:text-white text-xs font-extrabold transition">
                            ✨ ¿Nuevo aquí? Crea tu cuenta gratis →
                        </a>
                    @endguest
                </div>

                <h1 class="font-serif-editorial text-3xl sm:text-5xl font-extrabold tracking-tight leading-[1.12]">
                    Descubre Historias Épicas en <span class="bg-gradient-to-r from-amber-300 via-orange-400 to-rose-400 bg-clip-text text-transparent">NOVAETHER™</span>
                </h1>

                <p class="text-slate-300 text-sm sm:text-base max-w-2xl leading-relaxed">
                    La experiencia definitiva de librería moderna y bóveda digital. Explora mangas de culto, novelas gráficas, clásicos literarios e ingeniería de software con stock en tiempo real y lectura Cloud integrada.
                </p>

                <!-- Buscador rápido integrado en el Hero -->
                <form method="GET" action="{{ route('books.index') }}" class="pt-1">
                    <div class="flex flex-col sm:flex-row gap-2 bg-[#060913]/90 p-2 rounded-2xl border border-slate-700/80 focus-within:border-amber-400 transition shadow-inner">
                        <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}"
                               placeholder="🔍 Busca por título, autor, ISBN, One Piece, Batman, Dune, Clean Code..."
                               class="flex-1 bg-transparent px-4 py-2.5 text-sm text-white placeholder-slate-400 focus:outline-none">
                        <button type="submit"
                                class="bg-gradient-to-r from-amber-400 via-orange-500 to-rose-500 hover:from-amber-300 hover:to-rose-400 text-slate-950 font-black text-sm px-6 py-3 rounded-xl transition shadow-lg shadow-amber-500/20">
                            Explorar Ahora
                        </button>
                    </div>
                </form>

                <!-- Píldoras de tendencias -->
                <div class="flex flex-wrap items-center gap-2 pt-1 text-xs">
                    <span class="text-slate-400 font-bold">Explora rápido:</span>
                    <a href="{{ route('books.index', ['category_id' => $categories->firstWhere('nombre', 'Cómics, Manga y Novela Gráfica')?->id]) }}"
                       class="px-3.5 py-1.5 rounded-full bg-slate-900 border border-slate-700 hover:border-amber-400 text-amber-300 font-bold transition">
                        🎌 Manga & Cómics
                    </a>
                    <a href="{{ route('books.index', ['category_id' => $categories->firstWhere('nombre', 'Ciencia Ficción, Fantasía y Terror')?->id]) }}"
                       class="px-3.5 py-1.5 rounded-full bg-slate-900 border border-slate-700 hover:border-cyan-400 text-cyan-300 font-bold transition">
                        🚀 Sci-Fi & Fantasía
                    </a>
                    <a href="{{ route('books.index', ['category_id' => $categories->firstWhere('nombre', 'Tecnología, IA y Desarrollo (Tech)')?->id]) }}"
                       class="px-3.5 py-1.5 rounded-full bg-slate-900 border border-slate-700 hover:border-emerald-400 text-emerald-300 font-bold transition">
                        💻 Código & IA
                    </a>
                    <a href="{{ route('books.index', ['disponibilidad' => 'agotado']) }}"
                       class="px-3.5 py-1.5 rounded-full bg-rose-950/70 border border-rose-500/40 hover:bg-rose-600 text-rose-200 font-bold transition">
                        🔒 Ver Agotados (Stock 0)
                    </a>
                </div>
            </div>

            <!-- Panel de métricas y beneficios del club -->
            <div class="lg:col-span-5 grid grid-cols-2 gap-3.5">
                <div class="bg-[#0B1020]/90 border border-slate-800 hover:border-amber-400/40 p-5 rounded-2xl transition">
                    <div class="flex items-center justify-between">
                        <span class="text-2xl">📚</span>
                        <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-amber-400/15 text-amber-300">Catálogo</span>
                    </div>
                    <p class="text-3xl font-black text-white mt-2">{{ $stats['total_titulos'] }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">Obras y ediciones selectas</p>
                </div>

                <div class="bg-[#0B1020]/90 border border-slate-800 hover:border-emerald-400/40 p-5 rounded-2xl transition">
                    <div class="flex items-center justify-between">
                        <span class="text-2xl">⚡</span>
                        <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-emerald-400/15 text-emerald-300">En Vivo</span>
                    </div>
                    <p class="text-3xl font-black text-emerald-400 mt-2">{{ $stats['disponibles_ahora'] }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">Unidades listas para entrega</p>
                </div>

                <div class="bg-[#0B1020]/90 border border-slate-800 hover:border-cyan-400/40 p-5 rounded-2xl transition">
                    <div class="flex items-center justify-between">
                        <span class="text-2xl">🗂️</span>
                        <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-cyan-400/15 text-cyan-300">Géneros</span>
                    </div>
                    <p class="text-3xl font-black text-cyan-400 mt-2">{{ $stats['categorias_activas'] }}</p>
                    <p class="text-xs text-slate-400 mt-0.5">Colecciones especializadas</p>
                </div>

                <div class="bg-gradient-to-br from-amber-500/20 via-orange-500/15 to-rose-500/20 border border-amber-400/40 p-5 rounded-2xl flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-black uppercase text-amber-300">Membresía Club</span>
                        <p class="text-sm font-extrabold text-white mt-1">Lectura Cloud + Reserva Física</p>
                    </div>
                    @guest
                        <a href="{{ route('register') }}" class="mt-3 inline-block text-center bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black py-2 px-3 rounded-xl transition">
                            Unirme Gratis →
                        </a>
                    @else
                        <span class="mt-3 inline-block text-center bg-emerald-400/20 border border-emerald-400/40 text-emerald-300 text-xs font-bold py-1.5 px-3 rounded-xl">
                            ✅ Miembro Activo
                        </span>
                    @endguest
                </div>
            </div>
        </div>
    </div>
</div>

<!-- VITRINA COLECCIONISTA: MANGA, CÓMICS & GRAPHIC NOVELS -->
@if(empty($filtros['buscar']) && empty($filtros['category_id']) && empty($filtros['formato']) && empty($filtros['disponibilidad']) && $comicsDestacados->isNotEmpty())
<div class="mb-10 bg-gradient-to-r from-[#0E1528] via-[#13112C] to-[#0E1528] rounded-3xl p-6 sm:p-8 text-white shadow-2xl border border-slate-800">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="inline-flex items-center gap-2 text-xs font-black uppercase tracking-widest text-amber-400">
                <span>🎌 Selección Otaku & Cómic Americano</span>
            </div>
            <h2 class="text-2xl sm:text-3xl font-extrabold mt-1">
                Bóveda de Manga, Cómics y Novela Gráfica
            </h2>
            <p class="text-xs text-slate-400 mt-1">Ediciones coleccionista de Shueisha, Kodansha y DC Black Label con visor digital integrado.</p>
        </div>
        <a href="{{ route('books.index', ['category_id' => $comicsDestacados->first()->category_id]) }}"
           class="self-start sm:self-auto bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 text-xs font-black px-5 py-2.5 rounded-xl transition shadow-lg">
            Ver Colección Completa →
        </a>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        @foreach($comicsDestacados as $comic)
            <a href="{{ route('books.show', $comic) }}"
               class="group bg-[#080C17] hover:bg-[#0D1324] border border-slate-800 hover:border-amber-400/70 rounded-2xl p-3.5 flex flex-col justify-between transition duration-300 shadow-lg">
                <div>
                    <div class="aspect-[2/3] w-full rounded-xl overflow-hidden bg-slate-900 mb-3 relative shadow-md">
                        @if($comic->portada_url)
                            <img src="{{ $comic->portada_url }}" alt="{{ $comic->titulo }}"
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="hidden w-full h-full items-center justify-center text-4xl bg-slate-900">🎌</div>
                        @else
                            <div class="w-full h-full flex items-center justify-center text-4xl">🎌</div>
                        @endif
                        <span class="absolute top-2 right-2 text-[10px] font-black px-2.5 py-0.5 rounded-full shadow {{ $comic->estaDisponible() ? 'bg-emerald-400 text-slate-950' : 'bg-rose-600 text-white' }}">
                            {{ $comic->estaDisponible() ? $comic->stock_disponible . ' DISP.' : 'AGOTADO' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-1 text-[11px] text-amber-400 font-bold mb-1">
                        <span>★★★★★</span>
                        <span class="text-slate-400">(4.9)</span>
                    </div>
                    <h3 class="font-bold text-xs sm:text-sm text-white group-hover:text-amber-300 line-clamp-2 leading-snug">{{ $comic->titulo }}</h3>
                    <p class="text-[11px] text-slate-400 mt-1 truncate">{{ $comic->autor }}</p>
                </div>
                <div class="mt-3 pt-2.5 border-t border-slate-800/90 flex items-center justify-between text-[11px] text-amber-400 font-extrabold">
                    <span>📖 Leer / Reservar</span>
                    <span class="group-hover:translate-x-1 transition">→</span>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endif

<!-- NAVEGADOR INTERACTIVO DE LAS 12 CATEGORÍAS (100% Dark Glass) -->
<div class="mb-6">
    <div class="flex items-center justify-between mb-3">
        <h2 class="text-xs font-black uppercase tracking-widest text-amber-400">Explorar por Categorías ({{ $categories->count() }})</h2>
        @auth
            @if(auth()->user()->esAdmin())
                <a href="{{ route('books.create') }}" class="bg-gradient-to-r from-amber-400 to-orange-500 text-slate-950 text-xs font-black px-4 py-2 rounded-xl shadow transition">
                    ➕ Publicar Nuevo Libro / Cómic
                </a>
            @endif
        @endauth
    </div>

    <div class="flex gap-2 overflow-x-auto pb-2">
        <a href="{{ route('books.index') }}"
           class="flex-shrink-0 px-4 py-2.5 rounded-xl text-xs font-extrabold border transition {{ empty($filtros['category_id']) ? 'bg-amber-400 text-slate-950 border-amber-400 shadow-lg shadow-amber-500/20' : 'bg-[#0E1526] text-slate-300 border-slate-800 hover:border-amber-400/50 hover:text-white' }}">
            🌌 Todas ({{ $stats['total_titulos'] }})
        </a>
        @foreach($categories as $cat)
            <a href="{{ route('books.index', ['category_id' => $cat->id]) }}"
               class="flex-shrink-0 px-4 py-2.5 rounded-xl text-xs font-extrabold border transition flex items-center gap-2 {{ ($filtros['category_id'] ?? '') == $cat->id ? 'bg-amber-400 text-slate-950 border-amber-400 shadow-lg shadow-amber-500/20' : 'bg-[#0E1526] text-slate-300 border-slate-800 hover:border-amber-400/50 hover:text-white' }}">
                <span>{{ $cat->icono }}</span>
                <span>{{ $cat->nombre }}</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ ($filtros['category_id'] ?? '') == $cat->id ? 'bg-slate-950 text-amber-300' : 'bg-slate-800 text-slate-300' }}">{{ $cat->books_count }}</span>
            </a>
        @endforeach
    </div>
</div>

<!-- BARRA DE FILTROS AVANZADOS (100% Dark Glass) -->
<form method="GET" action="{{ route('books.index') }}" class="bg-[#0E1526]/90 backdrop-blur-md rounded-2xl border border-slate-800 p-4 mb-8 shadow-xl">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
        <div class="lg:col-span-2">
            <label class="block text-[11px] font-extrabold uppercase text-slate-400 mb-1">Buscar en catálogo</label>
            <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Título, autor, ISBN o editorial..."
                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white placeholder-slate-500 focus:border-amber-400 focus:outline-none">
        </div>

        <div>
            <label class="block text-[11px] font-extrabold uppercase text-slate-400 mb-1">Categoría</label>
            <select name="category_id" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white focus:border-amber-400 focus:outline-none">
                <option value="">Todas las colecciones</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(($filtros['category_id'] ?? '') == $cat->id)>
                        {{ $cat->icono }} {{ $cat->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-[11px] font-extrabold uppercase text-slate-400 mb-1">Formato y Stock</label>
            <div class="grid grid-cols-2 gap-1.5">
                <select name="formato" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-2 py-2 text-xs text-white">
                    <option value="">Formato</option>
                    <option value="ambos" @selected(($filtros['formato'] ?? '') === 'ambos')>Físico+Cloud</option>
                    <option value="digital" @selected(($filtros['formato'] ?? '') === 'digital')>Solo Cloud</option>
                    <option value="fisico" @selected(($filtros['formato'] ?? '') === 'fisico')>Solo Físico</option>
                </select>
                <select name="disponibilidad" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-2 py-2 text-xs text-white">
                    <option value="">Stock</option>
                    <option value="disponible" @selected(($filtros['disponibilidad'] ?? '') === 'disponible')>✅ En Stock</option>
                    <option value="agotado" @selected(($filtros['disponibilidad'] ?? '') === 'agotado')>⛔ Agotado</option>
                </select>
            </div>
        </div>

        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black py-2.5 px-4 rounded-xl transition shadow">
                🔍 Filtrar
            </button>
            <a href="{{ route('books.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold py-2.5 px-3 rounded-xl transition">
                Limpiar
            </a>
        </div>
    </div>
</form>

<!-- GRILLA DE TARJETAS 3D DARK LUXURY -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($books as $book)
        <div class="book-card-3d bg-[#0E1526]/95 rounded-2xl border border-slate-800 hover:border-amber-400/60 overflow-hidden flex flex-col justify-between shadow-xl">
            <div>
                <div class="p-5 flex gap-4">
                    <!-- Portada con brillo -->
                    <a href="{{ route('books.show', $book) }}"
                       class="w-28 h-42 flex-shrink-0 bg-slate-950 rounded-xl overflow-hidden shadow-lg border border-slate-700/80 flex items-center justify-center relative">
                        @if($book->portada_url)
                            <img src="{{ $book->portada_url }}" alt="{{ $book->titulo }}" class="cover-shine w-full h-full object-cover"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                            <div class="hidden w-full h-full items-center justify-center text-3xl bg-slate-900 text-white">📚</div>
                        @else
                            <span class="text-3xl text-white">📚</span>
                        @endif
                    </a>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-1 mb-1.5">
                            <span class="inline-block text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full bg-amber-400/15 text-amber-300 border border-amber-400/30 truncate">
                                {{ $book->category->icono }} {{ $book->category->nombre }}
                            </span>
                        </div>

                        <a href="{{ route('books.show', $book) }}" class="block group">
                            <h3 class="font-serif-editorial text-lg font-bold text-white group-hover:text-amber-300 transition leading-snug line-clamp-2">
                                {{ $book->titulo }}
                            </h3>
                        </a>

                        <p class="text-xs font-semibold text-slate-300 mt-1">✍️ {{ $book->autor }}</p>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-[11px] text-amber-400 font-bold">★★★★★ 4.9</span>
                            <span class="text-[11px] text-slate-500">· {{ $book->anio_publicacion ?? 'Clásico' }}</span>
                        </div>

                        <!-- Badges de Formato y Stock -->
                        <div class="mt-3 flex flex-wrap gap-1.5">
                            @if($book->formato === 'digital')
                                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-cyan-500/15 text-cyan-300 border border-cyan-500/30">
                                    ⚡ Cloud Digital
                                </span>
                            @elseif($book->formato === 'ambos')
                                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-fuchsia-500/15 text-fuchsia-300 border border-fuchsia-500/30">
                                    🔥 Físico + Cloud
                                </span>
                            @else
                                <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-slate-800 text-slate-300 border border-slate-700">
                                    📕 Físico
                                </span>
                            @endif

                            @if($book->estaDisponible())
                                <span class="text-[10px] font-black px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                    🟢 {{ $book->stock_disponible }}/{{ $book->stock_total }} Disp.
                                </span>
                            @else
                                <span class="text-[10px] font-black px-2 py-0.5 rounded-md bg-rose-500/20 text-rose-300 border border-rose-500/40">
                                    🔴 AGOTADO (0/{{ $book->stock_total }})
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                @if($book->sinopsis)
                    <p class="px-5 pb-4 text-xs text-slate-400 leading-relaxed line-clamp-2">
                        {{ $book->sinopsis }}
                    </p>
                @endif
            </div>

            <!-- Barra inferior de acciones -->
            <div class="bg-[#090D18] px-5 py-3.5 border-t border-slate-800/90 flex items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <a href="{{ route('books.show', $book) }}"
                       class="bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 text-xs font-bold px-3.5 py-2 rounded-xl transition flex items-center gap-1">
                        📖 Ver / Leer
                    </a>

                    @if($book->estaDisponible())
                        <a href="{{ route('loans.create', ['book_id' => $book->id]) }}"
                           class="bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 text-xs font-black px-3.5 py-2 rounded-xl transition shadow">
                            🔥 Reservar
                        </a>
                    @else
                        <a href="{{ route('loans.create', ['book_id' => $book->id]) }}"
                           class="bg-rose-950 hover:bg-rose-900 text-rose-300 border border-rose-700 text-xs font-extrabold px-3 py-2 rounded-xl transition">
                            🚫 Sin Stock (0)
                        </a>
                    @endif
                </div>

                @auth
                    @if(auth()->user()->esAdmin())
                        <div class="flex items-center gap-1">
                            <a href="{{ route('books.edit', $book) }}" class="text-xs text-slate-400 hover:text-amber-300 font-bold px-2 py-1" title="Editar">
                                ✏️
                            </a>
                            <form action="{{ route('books.destroy', $book) }}" method="POST" onsubmit="return confirm('¿Eliminar este libro del catálogo?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-rose-400 hover:text-rose-300 font-bold px-1.5 py-1" title="Eliminar">
                                    🗑️
                                </button>
                            </form>
                        </div>
                    @endif
                @endauth
            </div>
        </div>
    @empty
        <div class="col-span-3 bg-[#0E1526] rounded-2xl border border-slate-800 p-10 text-center">
            <div class="text-4xl mb-2">🔍</div>
            <p class="font-bold text-white">No encontramos obras con ese criterio.</p>
            <a href="{{ route('books.index') }}" class="inline-block mt-3 text-xs font-bold text-amber-400 hover:underline">Mostrar catálogo completo →</a>
        </div>
    @endforelse
</div>

<div class="mt-8">
    {{ $books->links() }}
</div>

@endsection
