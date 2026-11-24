@extends('layouts.app')

@section('title', 'Crear Préstamo Gratuito · NOVAETHER™')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-white">📖 {{ auth()->user()->esCliente() ? 'Solicitar Préstamo Gratuito' : 'Registrar Préstamo Gratuito' }}</h1>
            <p class="text-sm text-slate-300">Elige una obra y el plazo; recibirás un código QR para tu préstamo.</p>
        </div>
        <a href="{{ route('loans.index') }}" class="text-xs font-extrabold uppercase text-amber-400 hover:underline">← {{ auth()->user()->esCliente() ? 'Mis Préstamos' : 'Ver Préstamos' }}</a>
    </div>

    <div class="mb-5 bg-slate-900 border border-amber-400/30 rounded-2xl p-4 text-xs text-slate-200">
        <strong class="text-amber-300">📚 Préstamo de lectura abierta:</strong> Puedes tener hasta 3 préstamos activos simultáneos.
    </div>

    <form action="{{ route('loans.store') }}" method="POST" class="bg-slate-900/95 rounded-3xl border border-slate-800 p-6 sm:p-8 shadow-2xl space-y-5">
        @csrf

        <!-- Buscador en tiempo real del selector de libros -->
        <div>
            <label for="filtro_libro" class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">1. Seleccionar Obra del Catálogo *</label>
            <input type="text" id="filtro_libro" placeholder="🔎 Escribe título o autor para filtrar rápidamente..."
                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-xs text-white placeholder-slate-400 mb-2 focus:border-amber-400 focus:outline-none">

            <select id="book_id" name="book_id" required class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
                <option value="">Seleccione un libro, cómic o manga...</option>
                @foreach($books as $book)
                    <option value="{{ $book->id }}"
                            data-search="{{ mb_strtolower($book->titulo . ' ' . $book->autor . ' ' . $book->isbn) }}"
                            @selected(old('book_id', $selectedBookId) == $book->id)>
                        {{ $book->estaDisponible() ? '🟢' : '🔴 [SIN STOCK]' }}
                        {{ $book->titulo }} — {{ $book->autor }}
                        (Disp: {{ $book->stock_disponible }}/{{ $book->stock_total }})
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Solicitante -->
        @if(auth()->user()->esAdmin())
            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">2. Lector solicitante *</label>
                <select name="reader_id" required class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
                    <option value="">Seleccione un lector...</option>
                    @foreach($readers as $reader)
                        <option value="{{ $reader->id }}" @selected(old('reader_id') == $reader->id)>
                            {{ $reader->estado === 'activo' ? '🟢' : '🔴 [SUSPENDIDO]' }}
                            {{ $reader->nombre_completo }} — Doc: {{ $reader->dni_codigo }} ({{ $reader->etiquetaSegmento() }})
                        </option>
                    @endforeach
                </select>
            </div>
        @else
            @if($miPerfilReader)
                <input type="hidden" name="reader_id" value="{{ $miPerfilReader->id }}">
            @else
                <p class="text-xs text-rose-300">⚠️ Tu cuenta no posee una ficha de lector vinculada.</p>
            @endif
        @endif

        <!-- Plazo -->
        <div>
            <span class="block text-xs font-extrabold uppercase text-slate-300 mb-2">3. Duración del Préstamo *</span>
            <div class="grid grid-cols-3 gap-2.5">
                @foreach([7 => '⚡ Express (7 días)', 14 => '📖 Estándar (14 días)', 30 => '🏆 Extendido (30 días)'] as $dias => $etiqueta)
                    <label class="cursor-pointer">
                        <input type="radio" name="duracion_dias" value="{{ $dias }}" class="peer sr-only" @checked(old('duracion_dias', 7) == $dias)>
                        <div class="text-center rounded-xl border border-slate-700 bg-slate-950 p-3 text-xs font-bold text-slate-200 peer-checked:border-amber-400 peer-checked:bg-amber-400/15 peer-checked:text-amber-300 transition">
                            {{ $etiqueta }}
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-800">
            <div class="flex gap-2">
                <a href="{{ route('loans.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-700 text-xs font-bold text-slate-200 hover:bg-slate-800">Cancelar</a>
                <button type="submit" class="bg-linear-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 text-xs font-black px-6 py-2.5 rounded-xl shadow-lg">
                    🎫 Confirmar Préstamo Gratuito y Generar QR
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.getElementById('filtro_libro').addEventListener('input', function () {
    const termino = this.value.trim().toLowerCase();
    const select = document.getElementById('book_id');
    Array.from(select.options).forEach((opt) => {
        if (!opt.value) return;
        const text = opt.dataset.search || opt.textContent.toLowerCase();
        opt.hidden = termino !== '' && !text.includes(termino);
    });
});
</script>
@endsection
