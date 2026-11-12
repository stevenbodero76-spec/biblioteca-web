@extends('layouts.app')

@section('title', 'Editar Libro')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">✏️ Editar Libro</h1>
            <p class="text-sm text-slate-500">Modificando: {{ $book->titulo }}</p>
        </div>
        <a href="{{ route('books.index') }}" class="text-sm font-semibold text-slate-600 hover:underline">← Volver al catálogo</a>
    </div>

    <form action="{{ route('books.update', $book) }}" method="POST" class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Categoría *</label>
                <select name="category_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id', $book->category_id) == $cat->id)>
                            {{ $cat->icono }} {{ $cat->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Código ISBN *</label>
                <input type="text" name="isbn" value="{{ old('isbn', $book->isbn) }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Título del Libro *</label>
                <input type="text" name="titulo" value="{{ old('titulo', $book->titulo) }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Autor *</label>
                <input type="text" name="autor" value="{{ old('autor', $book->autor) }}" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Editorial</label>
                <input type="text" name="editorial" value="{{ old('editorial', $book->editorial) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Año de Publicación</label>
                <input type="number" name="anio_publicacion" value="{{ old('anio_publicacion', $book->anio_publicacion) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Formato *</label>
                <select name="formato" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="ambos" @selected(old('formato', $book->formato) === 'ambos')>Físico y Digital (Ambos)</option>
                    <option value="fisico" @selected(old('formato', $book->formato) === 'fisico')>Solo Físico</option>
                    <option value="digital" @selected(old('formato', $book->formato) === 'digital')>Solo Digital / Web</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Stock Total *</label>
                <input type="number" name="stock_total" value="{{ old('stock_total', $book->stock_total) }}" min="1" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Stock Disponible *</label>
                <input type="number" name="stock_disponible" value="{{ old('stock_disponible', $book->stock_disponible) }}" min="0" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Ubicación / Estante</label>
                <input type="text" name="ubicacion_estante" value="{{ old('ubicacion_estante', $book->ubicacion_estante) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">URL de Portada</label>
                <input type="url" name="portada_url" value="{{ old('portada_url', $book->portada_url) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Enlace a Libro Digital / PDF</label>
                <input type="url" name="archivo_pdf" value="{{ old('archivo_pdf', $book->archivo_pdf) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Sinopsis / Resumen</label>
                <textarea name="sinopsis" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">{{ old('sinopsis', $book->sinopsis) }}</textarea>
            </div>
        </div>

        <div class="pt-3 flex justify-end gap-2">
            <a href="{{ route('books.index') }}" class="px-4 py-2 rounded-lg border border-slate-300 text-sm font-semibold text-slate-700">Cancelar</a>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2 rounded-lg shadow">
                Actualizar Libro
            </button>
        </div>
    </form>
</div>
@endsection

