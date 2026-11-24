<div class="flex flex-wrap items-center justify-between gap-2 mb-4 text-xs text-slate-300">
    <span>
        Mostrando <strong class="text-white">{{ $books->count() }}</strong> de <strong class="text-amber-300">{{ $books->total() }}</strong> obras disponibles en el catálogo
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
        <div class="col-span-3 bg-slate-900 rounded-2xl border border-slate-800 p-10 text-center">
            <div class="text-4xl mb-2">🔍</div>
            <p class="font-bold text-white">No encontramos obras con ese criterio.</p>
            <a href="{{ route('books.index') }}" class="inline-block mt-3 text-xs font-bold text-amber-400 hover:underline">Mostrar catálogo completo →</a>
        </div>
    @endforelse
</div>

<div class="mt-8 ajax-pagination">
    {{ $books->links() }}
</div>
