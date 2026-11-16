@extends('layouts.app')

@section('title', 'Editar Obra · NOVAETHER™')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-white">✏️ Editar Obra</h1>
            <p class="text-sm text-slate-300">Modificando: <strong class="text-amber-300">{{ $book->titulo }}</strong></p>
        </div>
        <a href="{{ route('books.index') }}" class="text-xs font-extrabold uppercase text-amber-400 hover:underline">← Volver al catálogo</a>
    </div>

    <div class="mb-5 bg-slate-900 border border-amber-400/30 rounded-2xl p-4 text-xs text-slate-200">
        <strong>🛡️ Control de Integridad de Inventario:</strong> Actualmente hay <strong class="text-amber-300">{{ $prestamosActivos }} ejemplar(es)</strong> en préstamo activo. El stock total no puede ser menor a {{ $prestamosActivos }} y el disponible máximo permitido es <code>stock_total - {{ $prestamosActivos }}</code>.
    </div>

    <form action="{{ route('books.update', $book) }}" method="POST" enctype="multipart/form-data" class="bg-slate-900/95 rounded-3xl border border-slate-800 p-6 sm:p-8 shadow-2xl space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Categoría *</label>
                <select name="category_id" required class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id', $book->category_id) == $cat->id)>
                            {{ $cat->icono }} {{ $cat->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Código ISBN *</label>
                <input type="text" name="isbn" value="{{ old('isbn', $book->isbn) }}" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Título de la Obra *</label>
                <input type="text" name="titulo" value="{{ old('titulo', $book->titulo) }}" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Autor *</label>
                <input type="text" name="autor" value="{{ old('autor', $book->autor) }}" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Editorial</label>
                <input type="text" name="editorial" value="{{ old('editorial', $book->editorial) }}"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Año de Publicación</label>
                <input type="number" name="anio_publicacion" value="{{ old('anio_publicacion', $book->anio_publicacion) }}"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Formato *</label>
                <select name="formato" required class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
                    <option value="ambos" @selected(old('formato', $book->formato) === 'ambos')>Físico y Cloud Digital</option>
                    <option value="fisico" @selected(old('formato', $book->formato) === 'fisico')>Solo Físico</option>
                    <option value="digital" @selected(old('formato', $book->formato) === 'digital')>Solo Cloud Digital</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Stock Total * (Mín: {{ max(1, $prestamosActivos) }})</label>
                <input type="number" name="stock_total" value="{{ old('stock_total', $book->stock_total) }}" min="{{ max(1, $prestamosActivos) }}" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Stock Disponible *</label>
                <input type="number" name="stock_disponible" value="{{ old('stock_disponible', $book->stock_disponible) }}" min="0" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Precio Alquiler (S/)</label>
                <input type="number" step="0.10" name="precio_alquiler" value="{{ old('precio_alquiler', $book->precio_alquiler) }}" min="0"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Precio Venta Directa (S/)</label>
                <input type="number" step="0.10" name="precio_venta" value="{{ old('precio_venta', $book->precio_venta) }}" min="0"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Ubicación / Bóveda</label>
                <input type="text" name="ubicacion_estante" value="{{ old('ubicacion_estante', $book->ubicacion_estante) }}"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-amber-300 mb-1.5">Reemplazar Portada (Archivo local)</label>
                <input type="file" name="portada_file" accept="image/jpeg,image/png,image/webp"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs text-slate-300 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-amber-400 file:text-slate-950 file:font-black">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">URL / Ruta Actual de Portada</label>
                <input type="text" name="portada_url" value="{{ old('portada_url', $book->portada_url) }}"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-amber-300 mb-1.5">Subir / Reemplazar PDF Digital</label>
                <input type="file" name="pdf_file" accept="application/pdf"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs text-slate-300 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-sky-400 file:text-slate-950 file:font-black">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Enlace / Ruta Actual PDF Digital</label>
                <input type="text" name="archivo_pdf" value="{{ old('archivo_pdf', $book->archivo_pdf) }}"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Sinopsis / Reseña</label>
                <textarea name="sinopsis" rows="3" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">{{ old('sinopsis', $book->sinopsis) }}</textarea>
            </div>
        </div>

        <div class="pt-3 flex justify-end gap-2">
            <a href="{{ route('books.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-700 text-xs font-bold text-slate-200 hover:bg-slate-800">Cancelar</a>
            <button type="submit" class="bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 text-xs font-black px-6 py-2.5 rounded-xl shadow-lg">
                💾 Actualizar Obra
            </button>
        </div>
    </form>
</div>
@endsection
