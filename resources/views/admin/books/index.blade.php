@extends('layouts.app')

@section('title', 'Gestión de Libros y Cómics · Admin NOVAETHER™')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-white">📚 Gestión del Catálogo (Libros, Cómics y Mangas)</h1>
        <p class="text-sm text-slate-300">Sube nuevas obras, edita enlaces digitales/stock o retira títulos sin historial de préstamos.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('dashboard') }}" class="px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-bold text-slate-200 transition">
            ← Panel Admin
        </a>
        <a href="{{ route('admin.categories.index') }}" class="px-3.5 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-xs font-bold text-emerald-300 transition">
            🗂️ Categorías
        </a>
        <a href="{{ route('books.create') }}" class="bg-linear-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 text-xs font-black px-4 py-2.5 rounded-xl shadow-lg transition">
            ➕ Subir / Publicar Libro
        </a>
    </div>
</div>

<!-- Métricas rápidas -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-slate-900 rounded-2xl border border-slate-800 p-4 shadow-lg">
        <p class="text-[11px] font-extrabold uppercase text-slate-300">Total Obras</p>
        <p class="text-2xl font-black text-white mt-1">{{ $resumen['total_titulos'] }}</p>
    </div>
    <div class="bg-slate-900 rounded-2xl border border-slate-800 p-4 shadow-lg">
        <p class="text-[11px] font-extrabold uppercase text-slate-300">Ejemplares en Inventario</p>
        <p class="text-2xl font-black text-emerald-400 mt-1">{{ $resumen['total_ejemplares'] }}</p>
    </div>
    <div class="bg-slate-900 rounded-2xl border border-slate-800 p-4 shadow-lg">
        <p class="text-[11px] font-extrabold uppercase text-slate-300">Solo Digital</p>
        <p class="text-2xl font-black text-sky-400 mt-1">{{ $resumen['solo_digitales'] }}</p>
    </div>
    <div class="bg-slate-900 rounded-2xl border border-slate-800 p-4 shadow-lg">
        <p class="text-[11px] font-extrabold uppercase text-slate-300">Sin Stock Físico</p>
        <p class="text-2xl font-black text-rose-400 mt-1">{{ $resumen['sin_stock'] }}</p>
    </div>
</div>

<!-- Filtros de administración -->
<form method="GET" action="{{ route('admin.books.index') }}" class="bg-slate-900/95 rounded-2xl border border-slate-800 p-4 mb-6 shadow-xl">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
        <div class="lg:col-span-4">
            <label for="admin-book-search" class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1">Buscar título, autor o ISBN</label>
            <input type="text" id="admin-book-search" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Ej. One Piece, Cervantes, PD-..."
                   class="w-full h-10 rounded-xl bg-slate-950 border border-slate-700 px-3.5 text-sm text-white placeholder-slate-400 focus:border-amber-400 focus:outline-none">
        </div>

        <div class="lg:col-span-3">
            <label for="admin-book-category" class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1">Categoría</label>
            <select id="admin-book-category" name="category_id" class="w-full h-10 rounded-xl bg-slate-950 border border-slate-700 px-3 text-xs font-semibold text-white">
                <option value="">Todas las categorías</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(($filtros['category_id'] ?? '') == $cat->id)>
                        {{ $cat->icono }} {{ $cat->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="lg:col-span-2">
            <label for="admin-book-disp" class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1">Stock</label>
            <select id="admin-book-disp" name="disponibilidad" class="w-full h-10 rounded-xl bg-slate-950 border border-slate-700 px-3 text-xs font-semibold text-white">
                <option value="">Todo el stock</option>
                <option value="disponible" @selected(($filtros['disponibilidad'] ?? '') === 'disponible')>Con stock (&gt; 0)</option>
                <option value="agotado" @selected(($filtros['disponibilidad'] ?? '') === 'agotado')>Agotados (0)</option>
            </select>
        </div>

        <div class="lg:col-span-3 flex items-center gap-2">
            <button type="submit" class="flex-1 h-10 bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black px-4 rounded-xl transition shadow">
                🔍 Filtrar
            </button>
            <a href="{{ route('admin.books.index') }}" class="h-10 inline-flex items-center justify-center bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold px-3.5 rounded-xl transition">
                Limpiar
            </a>
        </div>
    </div>
</form>

<!-- Tabla administrativa de obras -->
<div class="bg-slate-900/95 rounded-3xl border border-slate-800 shadow-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="border-b border-slate-800 bg-slate-950/90 text-[11px] uppercase text-slate-300">
                    <th class="py-3.5 px-4">Obra</th>
                    <th class="py-3.5 px-3">Categoría / Formato</th>
                    <th class="py-3.5 px-3">Stock / Préstamos</th>
                    <th class="py-3.5 px-3">Enlace de Lectura</th>
                    <th class="py-3.5 px-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/80">
                @forelse($books as $book)
                    <tr class="hover:bg-slate-800/40 transition">
                        <td class="py-3 px-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-14 shrink-0 rounded-lg overflow-hidden bg-slate-950 border border-slate-700 flex items-center justify-center">
                                    @if($book->portada_url)
                                        <img src="{{ $book->portada_url }}" alt="{{ $book->titulo }}" loading="lazy" class="w-full h-full object-cover">
                                    @else
                                        <span class="text-lg">📚</span>
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <a href="{{ route('books.show', $book) }}" class="font-bold text-white hover:text-amber-300 line-clamp-1">
                                        {{ $book->titulo }}
                                    </a>
                                    <p class="text-xs text-slate-300 truncate">{{ $book->autor }}</p>
                                    <p class="text-[11px] text-slate-400 font-mono">ISBN: {{ $book->isbn }} · {{ $book->anio_publicacion ?? 'Clásico' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-3">
                            <span class="inline-block text-[10px] font-black px-2 py-0.5 rounded-full bg-amber-400/15 text-amber-300 border border-amber-400/30 mb-1">
                                {{ $book->category->icono }} {{ $book->category->nombre }}
                            </span>
                            <div class="flex flex-wrap gap-1 text-[10px] font-bold">
                                <span class="px-1.5 py-0.5 rounded bg-slate-800 text-slate-200 uppercase">{{ $book->formato }}</span>
                                <span class="px-1.5 py-0.5 rounded bg-sky-500/15 text-sky-200">{{ $book->etiquetaIdioma() }}</span>
                                <span class="px-1.5 py-0.5 rounded bg-violet-500/15 text-violet-200">{{ $book->etiquetaLicencia() }}</span>
                            </div>
                        </td>
                        <td class="py-3 px-3">
                            <span class="inline-block text-xs font-black px-2.5 py-0.5 rounded-full {{ $book->estaDisponible() ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-200' }}">
                                {{ $book->stock_disponible }} / {{ $book->stock_total }}
                            </span>
                            <p class="text-[11px] text-slate-400 mt-1">
                                Activos: <strong class="text-white">{{ $book->prestamos_activos_count }}</strong> · Hist.: {{ $book->loans_count }}
                            </p>
                        </td>
                        <td class="py-3 px-3">
                            @if($book->esPdfEmbeddable())
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-300">
                                    ✅ Visor embebido
                                </span>
                            @elseif($book->archivo_pdf)
                                <span class="inline-flex items-center gap-1 text-[11px] font-bold text-sky-300">
                                    🔗 Enlace externo
                                </span>
                            @else
                                <span class="text-[11px] text-rose-300 font-bold">Sin enlace</span>
                            @endif
                            <p class="text-[10px] text-slate-400 truncate max-w-44 font-mono mt-0.5">{{ $book->archivo_pdf }}</p>
                        </td>
                        <td class="py-3 px-4 text-right">
                            <div class="inline-flex items-center gap-1.5">
                                <a href="{{ route('books.read', $book) }}"
                                   class="px-2.5 py-1.5 rounded-lg bg-amber-400/15 hover:bg-amber-400/25 border border-amber-400/40 text-amber-300 text-xs font-bold transition"
                                   title="Abrir en Sala de Lectura">
                                    📖 Leer
                                </a>
                                <a href="{{ route('books.edit', $book) }}"
                                   class="px-2.5 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white text-xs font-bold transition"
                                   title="Editar metadatos, enlaces o stock">
                                    ✏️ Editar
                                </a>
                                <form action="{{ route('books.destroy', $book) }}" method="POST"
                                      data-confirm="¿Seguro que deseas quitar «{{ $book->titulo }}» del catálogo?">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="redirect_to" value="admin">
                                    <button type="submit"
                                            @disabled($book->loans_count > 0)
                                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold border transition {{ $book->loans_count > 0 ? 'bg-slate-950 border-slate-800 text-slate-500 cursor-not-allowed' : 'bg-rose-950 hover:bg-rose-800 text-rose-200 border-rose-500/40' }}"
                                            title="{{ $book->loans_count > 0 ? 'Bloqueado: tiene historial de préstamos' : 'Quitar del catálogo' }}">
                                        🗑️ Quitar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-10 px-4 text-center text-sm text-slate-300">
                            No se encontraron obras con los criterios seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-6">
    {{ $books->links() }}
</div>
@endsection
