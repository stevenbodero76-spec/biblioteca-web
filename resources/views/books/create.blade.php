@extends('layouts.app')

@section('title', 'Publicar Nueva Obra · NOVAETHER™')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-white">➕ Publicar Nuevo Libro, Cómic o Manga</h1>
            <p class="text-sm text-slate-300">Autocompletado por ISBN + carga local de portadas y archivos PDF.</p>
        </div>
        <a href="{{ route('books.index') }}" class="text-xs font-extrabold uppercase text-amber-400 hover:underline">← Volver al catálogo</a>
    </div>

    <form action="{{ route('books.store') }}" method="POST" enctype="multipart/form-data" class="bg-slate-900/95 rounded-3xl border border-slate-800 p-6 sm:p-8 shadow-2xl space-y-5">
        @csrf

        <!-- Buscador automático por ISBN -->
        <div class="bg-slate-950 border border-amber-400/30 rounded-2xl p-4">
            <label for="isbn" class="block text-xs font-extrabold uppercase text-amber-300 mb-2">Código ISBN + Autocompletado Inteligente *</label>
            <div class="flex flex-col sm:flex-row gap-2">
                <input type="text" id="isbn" name="isbn" value="{{ old('isbn') }}" required placeholder="Ej. 9780132350884 o 9788497592208"
                       class="flex-1 rounded-xl bg-slate-900 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-400 focus:border-amber-400 focus:outline-none">
                <button type="button" id="btn-isbn-lookup"
                        class="bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black px-4 py-2.5 rounded-xl transition whitespace-nowrap">
                    ⚡ Autocompletar por ISBN
                </button>
            </div>
            <p id="isbn-feedback" class="text-xs text-slate-300 mt-2 hidden"></p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Categoría *</label>
                <select name="category_id" required class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
                    <option value="">Seleccione una categoría...</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>
                            {{ $cat->icono }} {{ $cat->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Formato *</label>
                <select name="formato" required class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
                    <option value="ambos" @selected(old('formato', 'ambos') === 'ambos')>Físico y Cloud Digital</option>
                    <option value="fisico" @selected(old('formato') === 'fisico')>Solo Físico</option>
                    <option value="digital" @selected(old('formato') === 'digital')>Solo Cloud Digital</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Título de la Obra *</label>
                <input type="text" id="titulo" name="titulo" value="{{ old('titulo') }}" required placeholder="Nombre completo de la obra"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-400 focus:border-amber-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Autor *</label>
                <input type="text" id="autor" name="autor" value="{{ old('autor') }}" required placeholder="Ej. Gabriel García Márquez"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-400 focus:border-amber-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Editorial</label>
                <input type="text" id="editorial" name="editorial" value="{{ old('editorial') }}" placeholder="Ej. Alfaguara / Shueisha"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-400 focus:border-amber-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Año de Publicación</label>
                <input type="number" id="anio_publicacion" name="anio_publicacion" value="{{ old('anio_publicacion', date('Y')) }}"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Ubicación / Bóveda</label>
                <input type="text" name="ubicacion_estante" value="{{ old('ubicacion_estante', 'Estante General') }}"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Stock Total *</label>
                <input type="number" name="stock_total" value="{{ old('stock_total', 3) }}" min="1" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Stock Disponible *</label>
                <input type="number" name="stock_disponible" value="{{ old('stock_disponible', 3) }}" min="0" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Precio Alquiler (S/)</label>
                <input type="number" step="0.10" name="precio_alquiler" value="{{ old('precio_alquiler', '8.00') }}" min="0"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Precio Venta Directa (S/)</label>
                <input type="number" step="0.10" name="precio_venta" value="{{ old('precio_venta', '49.90') }}" min="0"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
            </div>

            <!-- Carga local de portada + URL respaldo -->
            <div>
                <label class="block text-xs font-extrabold uppercase text-amber-300 mb-1.5">Subir Portada (JPG/PNG/WEBP)</label>
                <input type="file" name="portada_file" accept="image/jpeg,image/png,image/webp"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs text-slate-300 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-amber-400 file:text-slate-950 file:font-black">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">O URL Externa de Portada</label>
                <input type="text" id="portada_url" name="portada_url" value="{{ old('portada_url') }}" placeholder="https://covers.openlibrary.org/..."
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-400 focus:border-amber-400 focus:outline-none">
            </div>

            <!-- Carga local de PDF + URL respaldo -->
            <div>
                <label class="block text-xs font-extrabold uppercase text-amber-300 mb-1.5">Subir PDF de Muestra / E-Book</label>
                <input type="file" name="pdf_file" accept="application/pdf"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs text-slate-300 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-sky-400 file:text-slate-950 file:font-black">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">O Enlace Digital Externo</label>
                <input type="text" name="archivo_pdf" value="{{ old('archivo_pdf') }}" placeholder="https://..."
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-400 focus:border-amber-400 focus:outline-none">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Sinopsis / Reseña</label>
                <textarea id="sinopsis" name="sinopsis" rows="3" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-400 focus:border-amber-400 focus:outline-none">{{ old('sinopsis') }}</textarea>
            </div>
        </div>

        <div class="pt-3 flex justify-end gap-2">
            <a href="{{ route('books.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-700 text-xs font-bold text-slate-200 hover:bg-slate-800">Cancelar</a>
            <button type="submit" class="bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 text-xs font-black px-6 py-2.5 rounded-xl shadow-lg">
                💾 Guardar Obra
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('btn-isbn-lookup').addEventListener('click', async () => {
    const isbnInput = document.getElementById('isbn');
    const feedback = document.getElementById('isbn-feedback');
    const btn = document.getElementById('btn-isbn-lookup');
    const isbn = isbnInput.value.trim();

    feedback.classList.remove('hidden', 'text-emerald-300', 'text-rose-300', 'text-slate-300');

    if (!isbn) {
        feedback.textContent = '⚠️ Ingresa un código ISBN primero.';
        feedback.classList.add('text-rose-300');
        return;
    }

    btn.disabled = true;
    btn.textContent = '⏳ Buscando...';
    feedback.textContent = 'Consultando catálogo internacional Open Library...';
    feedback.classList.add('text-slate-300');

    try {
        const res = await fetch(`/api/isbn/${encodeURIComponent(isbn)}`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        if (!res.ok || !data.encontrado) {
            feedback.textContent = '⚠️ ' + (data.mensaje || 'No se encontraron metadatos para este ISBN.');
            feedback.classList.replace('text-slate-300', 'text-rose-300');
            return;
        }

        if (data.titulo) document.getElementById('titulo').value = data.titulo;
        if (data.autor) document.getElementById('autor').value = data.autor;
        if (data.editorial) document.getElementById('editorial').value = data.editorial;
        if (data.anio_publicacion) document.getElementById('anio_publicacion').value = data.anio_publicacion;
        if (data.portada_url) document.getElementById('portada_url').value = data.portada_url;
        if (data.sinopsis && !document.getElementById('sinopsis').value) {
            document.getElementById('sinopsis').value = data.sinopsis;
        }

        feedback.textContent = '✅ Metadatos y portada completados automáticamente.';
        feedback.classList.replace('text-slate-300', 'text-emerald-300');
    } catch (e) {
        feedback.textContent = '⚠️ Error de red al consultar el ISBN.';
        feedback.classList.replace('text-slate-300', 'text-rose-300');
    } finally {
        btn.disabled = false;
        btn.textContent = '⚡ Autocompletar por ISBN';
    }
});
</script>
@endsection
