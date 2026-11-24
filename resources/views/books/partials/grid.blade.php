<div class="flex flex-wrap items-center justify-between gap-2 mb-4 text-xs text-slate-300">
    <span>
        Mostrando <strong class="text-white">{{ $books->count() }}</strong> de <strong class="text-amber-300">{{ $books->total() }}</strong> obras disponibles en el catálogo local
    </span>
    @if(!empty($filtros['buscar']) || !empty($filtros['category_id']) || !empty($filtros['formato']) || !empty($filtros['idioma']) || !empty($filtros['licencia']) || !empty($filtros['disponibilidad']) || !empty($filtros['orden']))
        <span class="px-2.5 py-1 rounded-lg bg-amber-400/15 text-amber-300 border border-amber-400/30 font-bold">
            Filtros activos
        </span>
    @endif
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($books as $book)
        <x-book-card :book="$book" :esFavorito="in_array($book->id, $favoriteBookIds)" />
    @empty
        <div class="col-span-3 bg-slate-900 rounded-2xl border border-slate-800 p-8 text-center">
            <div class="text-4xl mb-2">🔍</div>
            <p class="font-bold text-white text-base">No encontramos «{{ $filtros['buscar'] ?? 'obras' }}» en el catálogo local.</p>
            <p class="text-xs text-slate-300 mt-1">Revisa abajo los resultados en vivo obtenidos directamente de la Red Abierta Mundial.</p>
            <a href="{{ route('books.index') }}" class="inline-block mt-3 text-xs font-bold text-amber-400 hover:underline">Mostrar todo el catálogo local →</a>
        </div>
    @endforelse

    @if(!empty($librosExternos))
        <div class="col-span-1 md:col-span-2 lg:col-span-3 mt-4">
            <div class="bg-gradient-to-r from-amber-950/40 via-slate-900 to-slate-900 border border-amber-500/30 rounded-3xl p-6 sm:p-8 shadow-2xl">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6 pb-4 border-b border-slate-800">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-400 text-slate-950">Red Global Abierta</span>
                            <span class="text-xs text-amber-300 font-bold">Open Library · Proyecto Gutenberg</span>
                        </div>
                        <h3 class="text-xl font-black text-white mt-1">
                            🌐 Obras encontradas en Internet para «{{ $filtros['buscar'] ?? '' }}»
                        </h3>
                        <p class="text-xs text-slate-300 mt-0.5">
                            No estaban en la base de datos local, pero puedes leerlas gratis de inmediato o importarlas con 1 clic.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($librosExternos as $ext)
                        <div class="bg-slate-950/90 rounded-2xl border border-slate-800 hover:border-amber-400/50 p-4 transition duration-300 flex flex-col justify-between shadow-lg">
                            <div>
                                <div class="flex items-start gap-3 mb-3">
                                    <div class="w-16 h-24 rounded-xl overflow-hidden bg-slate-900 flex-shrink-0 border border-slate-800 flex items-center justify-center">
                                        @if(!empty($ext['portada_url']))
                                            <img src="{{ $ext['portada_url'] }}" alt="{{ $ext['titulo'] }}" loading="lazy" class="w-full h-full object-cover"
                                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="hidden w-full h-full items-center justify-center text-2xl">📚</div>
                                        @else
                                            <span class="text-3xl">📚</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-amber-300 border border-slate-700 mb-1">
                                            <span>{{ $ext['fuente_icono'] }}</span>
                                            <span>{{ $ext['fuente'] }}</span>
                                        </span>
                                        <h4 class="font-bold text-sm text-white line-clamp-2">{{ $ext['titulo'] }}</h4>
                                        <p class="text-xs text-amber-300/90 truncate mt-0.5">✍️ {{ $ext['autor'] }}</p>
                                        @if(!empty($ext['anio_publicacion']))
                                            <span class="text-[11px] text-slate-400">📅 {{ $ext['anio_publicacion'] }}</span>
                                        @endif
                                    </div>
                                </div>
                                <p class="text-[11px] text-slate-300 line-clamp-2 mb-3 bg-slate-900/60 p-2 rounded-xl border border-slate-800/80">
                                    {{ $ext['sinopsis'] }}
                                </p>
                            </div>

                            <div class="pt-3 border-t border-slate-800/80 flex flex-col gap-2">
                                @auth
                                    <a href="{{ $ext['archivo_pdf'] }}" target="_blank" rel="noopener noreferrer"
                                       class="w-full inline-flex items-center justify-center gap-1.5 bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black text-xs py-2 px-3 rounded-xl transition shadow">
                                        <span>📖 Leer Gratis en Línea</span>
                                        <span>↗</span>
                                    </a>
                                @else
                                    <a href="{{ route('login') }}"
                                       class="w-full inline-flex items-center justify-center gap-1.5 bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black text-xs py-2 px-3 rounded-xl transition shadow">
                                        <span>🔒 Inicia sesión · Leer Gratis en Línea</span>
                                        <span>→</span>
                                    </a>
                                @endauth

                                @auth
                                    @if(auth()->user()->esAdmin())
                                        <form action="{{ route('books.importar-externo') }}" method="POST" class="w-full">
                                            @csrf
                                            <input type="hidden" name="titulo" value="{{ $ext['titulo'] }}">
                                            <input type="hidden" name="autor" value="{{ $ext['autor'] }}">
                                            <input type="hidden" name="editorial" value="{{ $ext['editorial'] }}">
                                            <input type="hidden" name="anio_publicacion" value="{{ $ext['anio_publicacion'] ?? '' }}">
                                            <input type="hidden" name="isbn" value="{{ $ext['isbn'] ?? '' }}">
                                            <input type="hidden" name="portada_url" value="{{ $ext['portada_url'] ?? '' }}">
                                            <input type="hidden" name="archivo_pdf" value="{{ $ext['archivo_pdf'] }}">
                                            <button type="submit"
                                                    class="w-full inline-flex items-center justify-center gap-1 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-black text-xs py-1.5 px-3 rounded-xl transition shadow">
                                                <span>➕ Importar al Catálogo en 1 Clic</span>
                                            </button>
                                        </form>
                                    @endif
                                @endauth
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

<div class="mt-8 ajax-pagination">
    {{ $books->links() }}
</div>
