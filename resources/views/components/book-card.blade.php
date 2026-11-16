@props(['book', 'esFavorito' => false])

@php
    $quickViewPayload = [
        'id' => $book->id,
        'titulo' => $book->titulo,
        'autor' => $book->autor,
        'editorial' => $book->editorial ?? 'Edición Oficial',
        'anio' => $book->anio_publicacion ?? 'Clásico',
        'isbn' => $book->isbn,
        'categoria' => ($book->category->icono ?? '📚') . ' ' . ($book->category->nombre ?? 'General'),
        'formato' => strtoupper($book->formato),
        'sinopsis' => $book->sinopsis ?? 'Obra disponible en el catálogo oficial de NOVAETHER™.',
        'portada_url' => $book->portada_url,
        'disponible' => $book->estaDisponible(),
        'stock_texto' => $book->estaDisponible()
            ? "{$book->stock_disponible} de {$book->stock_total} disponibles"
            : "AGOTADO (0 de {$book->stock_total})",
        'precio_alquiler' => number_format((float) $book->precio_alquiler, 2),
        'precio_venta' => number_format((float) $book->precio_venta, 2),
        'rating_texto' => ($book->reviews_count ?? 0) > 0
            ? '★ ' . number_format((float) $book->reviews_avg_rating, 1) . ' (' . $book->reviews_count . ' reseñas)'
            : '✨ Estreno sin reseñas aún',
        'show_url' => route('books.show', $book),
        'reserve_url' => route('loans.create', ['book_id' => $book->id]),
        'cart_url' => route('cart.add', $book),
    ];
@endphp

<div class="book-card-3d bg-slate-900/95 rounded-2xl border border-slate-800 hover:border-amber-400/60 overflow-hidden flex flex-col justify-between shadow-xl">
    <div>
        <div class="p-5 flex gap-4">
            <!-- Portada -->
            <a href="{{ route('books.show', $book) }}"
               class="w-28 h-40 flex-shrink-0 bg-slate-950 rounded-xl overflow-hidden shadow-lg border border-slate-700/80 flex items-center justify-center relative">
                @if($book->portada_url)
                    <img src="{{ $book->portada_url }}" alt="Portada de {{ $book->titulo }}" class="cover-shine w-full h-full object-cover"
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

                    @auth
                        <form action="{{ route('favorites.toggle', $book) }}" method="POST" class="flex-shrink-0 js-favorite-form">
                            @csrf
                            <button type="submit" data-no-loading="true"
                                    class="js-favorite-btn text-sm leading-none p-1 rounded-lg hover:bg-slate-800 transition transform active:scale-125"
                                    title="{{ $esFavorito ? 'Quitar de Mi Estantería' : 'Guardar en Mi Estantería' }}">
                                {{ $esFavorito ? '❤️' : '🤍' }}
                            </button>
                        </form>
                    @endauth
                </div>

                <a href="{{ route('books.show', $book) }}" class="block group">
                    <h3 class="font-serif-editorial text-lg font-bold text-white group-hover:text-amber-300 transition leading-snug line-clamp-2">
                        {{ $book->titulo }}
                    </h3>
                </a>

                <p class="text-xs font-semibold text-slate-200 mt-1">✍️ {{ $book->autor }}</p>

                <!-- Calificación real -->
                <div class="flex items-center gap-2 mt-1">
                    @if(($book->reviews_count ?? 0) > 0)
                        <span class="text-[11px] text-amber-400 font-bold">
                            ★ {{ number_format((float) $book->reviews_avg_rating, 1) }}
                        </span>
                        <span class="text-[11px] text-slate-300">({{ $book->reviews_count }} {{ $book->reviews_count === 1 ? 'reseña' : 'reseñas' }})</span>
                    @else
                        <span class="text-[11px] text-slate-300">✨ Estreno · Sin reseñas</span>
                    @endif
                </div>

                <!-- Precios + Stock -->
                <div class="mt-2.5 flex flex-wrap gap-1.5">
                    <span class="text-[10px] font-black px-2 py-0.5 rounded-md bg-slate-800 text-amber-300 border border-slate-700">
                        Alq. S/ {{ number_format((float) $book->precio_alquiler, 2) }} · Venta S/ {{ number_format((float) $book->precio_venta, 2) }}
                    </span>

                    @if($book->estaDisponible())
                        <span class="text-[10px] font-black px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            🟢 {{ $book->stock_disponible }}/{{ $book->stock_total }} Disp.
                        </span>
                    @else
                        <span class="text-[10px] font-black px-2 py-0.5 rounded-md bg-rose-500/20 text-rose-200 border border-rose-500/40">
                            🔴 AGOTADO
                        </span>
                    @endif
                </div>
            </div>
        </div>

        @if($book->sinopsis)
            <p class="px-5 pb-4 text-xs text-slate-300 leading-relaxed line-clamp-2">
                {{ $book->sinopsis }}
            </p>
        @endif
    </div>

    <!-- Barra inferior: Quick View + Comprar Carrito + Alquilar/Espera -->
    <div class="bg-slate-950/90 px-4 py-3.5 border-t border-slate-800 flex items-center justify-between gap-1.5">
        <button type="button"
                data-book='@json($quickViewPayload)'
                class="js-quickview-btn h-9 px-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-slate-100 text-xs font-bold transition inline-flex items-center gap-1"
                title="Vista Rápida">
            <svg class="w-3.5 h-3.5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <span class="hidden sm:inline">Ver</span>
        </button>

        @if($book->estaDisponible())
            @auth
                <form action="{{ route('cart.add', $book) }}" method="POST" class="js-cart-add-form">
                    @csrf
                    <input type="hidden" name="tipo_operacion" value="venta">
                    <button type="submit" data-no-loading="true"
                            class="h-9 px-3 rounded-xl bg-emerald-500/20 hover:bg-emerald-500 text-emerald-300 hover:text-slate-950 border border-emerald-500/40 text-xs font-black transition whitespace-nowrap"
                            title="Añadir compra directa al carrito">
                        🛒 S/ {{ number_format((float) $book->precio_venta, 2) }}
                    </button>
                </form>
            @endauth

            <a href="{{ route('loans.create', ['book_id' => $book->id]) }}"
               class="flex-1 h-9 inline-flex items-center justify-center bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 text-xs font-black px-3 rounded-xl transition shadow truncate">
                Alquilar
            </a>
        @else
            <a href="{{ route('books.show', $book) }}"
               class="flex-1 h-9 inline-flex items-center justify-center bg-rose-950/90 hover:bg-rose-900 text-rose-200 border border-rose-700 text-xs font-extrabold px-4 rounded-xl transition">
                Lista de Espera
            </a>
        @endif

        @auth
            @if(auth()->user()->esAdmin())
                <div class="flex items-center gap-0.5">
                    <a href="{{ route('books.edit', $book) }}" class="text-xs text-slate-300 hover:text-amber-300 font-bold px-1.5 py-1" title="Editar obra">
                        ✏️
                    </a>
                    <form action="{{ route('books.destroy', $book) }}" method="POST" data-confirm="¿Seguro que deseas eliminar «{{ $book->titulo }}» del catálogo?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-rose-400 hover:text-rose-300 font-bold px-1 py-1" title="Eliminar obra">
                            🗑️
                        </button>
                    </form>
                </div>
            @endif
        @endauth
    </div>
</div>
