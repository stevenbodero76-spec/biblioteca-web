@extends('layouts.app')

@section('title', 'Sala de Lectura · ' . $book->titulo . ' · NOVAETHER™')

@section('content')

<div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-slate-900/95 border border-slate-800 rounded-2xl p-4 shadow-xl">
    <div class="flex items-center gap-3 min-w-0">
        <a href="{{ route('books.show', $book) }}"
           class="shrink-0 bg-slate-800 hover:bg-slate-700 border border-slate-700 text-amber-300 text-xs font-extrabold px-3.5 py-2 rounded-xl transition">
            ← Ficha
        </a>
        <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="text-[10px] font-black uppercase px-2 py-0.5 rounded bg-amber-400/15 text-amber-300 border border-amber-400/30">
                    {{ $book->category->icono }} {{ $book->category->nombre }}
                </span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-sky-500/15 text-sky-200 border border-sky-500/30">
                    {{ $book->idioma === 'es' ? '🇪🇸 Español' : ($book->idioma === 'en' ? '🇬🇧 Inglés' : '🌐 Idioma') }}
                </span>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-violet-500/15 text-violet-200 border border-violet-500/30">
                    ⚖️ {{ $book->etiquetaLicencia() }}
                </span>
            </div>
            <h1 class="font-serif-editorial text-lg sm:text-xl font-extrabold text-white truncate mt-1">
                {{ $book->titulo }}
            </h1>
            <p class="text-xs text-slate-300 truncate">✍️ {{ $book->autor }}</p>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-2 shrink-0">
        <a href="{{ route('books.download', $book) }}" target="_blank" rel="noopener noreferrer"
           class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-sky-300 text-xs font-bold px-3.5 py-2 rounded-xl transition">
            ⬇️ Descarga / Pantalla Completa ↗
        </a>
        @if($book->estaDisponible())
            <a href="{{ route('loans.create', ['book_id' => $book->id]) }}"
               class="bg-emerald-500/20 hover:bg-emerald-500/30 border border-emerald-400/50 text-emerald-200 text-xs font-extrabold px-3.5 py-2 rounded-xl transition">
                🎫 Préstamo Gratis
            </a>
        @endif
    </div>
</div>

<!-- VISOR PRINCIPAL DE SALA DE LECTURA -->
<div class="rounded-3xl border border-slate-800 bg-slate-950 text-slate-100 p-4 sm:p-6 shadow-2xl mb-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 pb-4 border-b border-slate-800">
        <div class="flex items-center gap-2.5">
            <span class="text-xl">🖥️</span>
            <div>
                <h2 class="text-sm sm:text-base font-extrabold text-amber-400">Sala de Lectura Exclusiva NOVAETHER™</h2>
                <p class="text-xs text-slate-300">Sesión verificada para {{ auth()->user()->name }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-xs">
            @if($book->esPdfEmbeddable())
                <button type="button" onclick="alternarModoLectura('pdf')" id="btn-read-pdf" class="px-3 py-1.5 rounded-lg bg-amber-400 text-slate-950 font-black">
                    📄 Visor Completo
                </button>
                <button type="button" onclick="alternarModoLectura('texto')" id="btn-read-texto" class="px-3 py-1.5 rounded-lg bg-slate-800 text-white font-bold border border-slate-700">
                    📝 Sinopsis &amp; Notas
                </button>
                <span class="h-4 w-px bg-slate-700 mx-1"></span>
            @endif
            <button type="button" onclick="ajustarFuenteLectura(-1)" class="px-2.5 py-1.5 rounded-lg bg-slate-800 text-white font-bold border border-slate-700">A-</button>
            <button type="button" onclick="ajustarFuenteLectura(1)" class="px-2.5 py-1.5 rounded-lg bg-slate-800 text-white font-bold border border-slate-700">A+</button>
            <button type="button" onclick="temaLectura('oscuro')" class="px-2.5 py-1.5 rounded-lg bg-slate-800 text-white font-bold border border-slate-700">🌙</button>
            <button type="button" onclick="temaLectura('sepia')" class="px-2.5 py-1.5 rounded-lg bg-[#F4ECD8] text-amber-950 font-bold">📜</button>
            <button type="button" onclick="temaLectura('claro')" class="px-2.5 py-1.5 rounded-lg bg-white text-slate-900 font-bold">☀️</button>
        </div>
    </div>

    @if($book->esPdfEmbeddable())
        <div id="read-pdf-panel" class="mt-4">
            <div class="rounded-2xl overflow-hidden border border-slate-800 bg-slate-900">
                <iframe src="{{ $book->visor_embed_url ?? $book->archivo_pdf }}"
                        title="Visor de lectura: {{ $book->titulo }}"
                        class="w-full h-[72vh] min-h-[520px]"></iframe>
            </div>
        </div>
    @elseif($book->archivo_pdf)
        <div class="mt-4 rounded-2xl border border-amber-400/30 bg-slate-900/90 p-6 sm:p-8 text-center space-y-4">
            <div class="text-4xl">📖</div>
            <h3 class="text-lg font-extrabold text-white">Edición Digital Oficial Lista para Lectura</h3>
            <p class="text-xs sm:text-sm text-slate-300 max-w-xl mx-auto">
                Esta obra se lee directamente en su portal oficial externo (como Shueisha MANGA Plus o Proyecto Gutenberg) para respetar los capítulos gratuitos autorizados.
            </p>
            <div class="flex flex-wrap justify-center gap-3 pt-1">
                <a href="{{ $book->archivo_pdf }}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 bg-linear-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 font-black text-sm px-6 py-3 rounded-xl shadow-lg transition">
                    📖 Abrir Obra Completa en Fuente Oficial ↗
                </a>
            </div>
        </div>
    @endif

    <div id="read-texto-panel" class="{{ $book->esPdfEmbeddable() ? 'hidden' : 'mt-6' }}">
        <div id="read-hoja-texto" class="mt-4 max-w-3xl mx-auto rounded-2xl bg-slate-900 border border-slate-800 p-6 sm:p-8 leading-relaxed transition-colors duration-300 text-base space-y-4">
            <p class="text-xs uppercase tracking-widest text-amber-400 font-bold">{{ $book->editorial ?? 'Edición Verificada' }} · {{ $book->anio_publicacion ?? 'Clásico' }}</p>
            <h3 class="font-serif-editorial text-2xl font-bold">{{ $book->titulo }}</h3>
            <p>{{ $book->sinopsis }}</p>
            <p class="text-xs opacity-80">Ubicación física / digital: {{ $book->ubicacion_estante ?? 'Bóveda Digital NOVAETHER™' }}</p>
        </div>
    </div>
</div>

<!-- FUENTES INTERCONECTADAS -->
<section class="mb-8 rounded-3xl border border-slate-800 bg-slate-900/90 p-5 sm:p-6 shadow-xl">
    <h2 class="text-sm font-extrabold text-white mb-3">📚 Fuentes Alternativas Verificadas</h2>
    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-5">
        @foreach($enlacesLectura['fuentes'] as $fuente)
            <a href="{{ $fuente['url'] }}" target="_blank" rel="noopener noreferrer"
               class="flex items-center gap-3 rounded-xl border border-slate-700 bg-slate-950 p-3 text-slate-200 transition hover:border-amber-400/60 hover:bg-slate-900">
                <span class="text-xl">{{ $fuente['icono'] }}</span>
                <div class="min-w-0">
                    <span class="block text-xs font-extrabold text-white truncate">{{ $fuente['nombre'] }} ↗</span>
                    <span class="block text-[10px] text-slate-400 truncate">{{ $fuente['tipo'] }}</span>
                </div>
            </a>
        @endforeach
    </div>
</section>

<!-- LECTURAS RELACIONADAS -->
@if($relacionados->isNotEmpty())
<section class="rounded-3xl border border-slate-800 bg-slate-900/90 p-5 sm:p-6 shadow-xl">
    <h2 class="text-sm font-extrabold text-white mb-3">🕸️ Continúa leyendo obras relacionadas</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
        @foreach($relacionados as $rel)
            <a href="{{ route('books.read', $rel) }}" class="flex gap-3 rounded-2xl border border-slate-800 bg-slate-950 p-3 hover:border-amber-400/60 transition">
                <div class="h-20 w-14 shrink-0 overflow-hidden rounded-lg bg-slate-900 flex items-center justify-center">
                    @if($rel->portada_url)
                        <img src="{{ $rel->portada_url }}" alt="{{ $rel->titulo }}" loading="lazy" class="h-full w-full object-cover">
                    @else
                        <span>📚</span>
                    @endif
                </div>
                <div class="min-w-0">
                    <h3 class="text-xs font-bold text-white line-clamp-2">{{ $rel->titulo }}</h3>
                    <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ $rel->autor }}</p>
                    <span class="inline-block mt-1.5 text-[10px] font-bold text-amber-300">📖 Leer ahora →</span>
                </div>
            </a>
        @endforeach
    </div>
</section>
@endif

<script>
let readFontSize = 16;

function alternarModoLectura(modo) {
    const pdfPanel = document.getElementById('read-pdf-panel');
    const txtPanel = document.getElementById('read-texto-panel');
    const btnPdf = document.getElementById('btn-read-pdf');
    const btnTxt = document.getElementById('btn-read-texto');
    if (!pdfPanel || !txtPanel) return;

    if (modo === 'pdf') {
        pdfPanel.classList.remove('hidden');
        txtPanel.classList.add('hidden');
        btnPdf.className = 'px-3 py-1.5 rounded-lg bg-amber-400 text-slate-950 font-black';
        btnTxt.className = 'px-3 py-1.5 rounded-lg bg-slate-800 text-white font-bold border border-slate-700';
    } else {
        pdfPanel.classList.add('hidden');
        txtPanel.classList.remove('hidden');
        btnTxt.className = 'px-3 py-1.5 rounded-lg bg-amber-400 text-slate-950 font-black';
        btnPdf.className = 'px-3 py-1.5 rounded-lg bg-slate-800 text-white font-bold border border-slate-700';
    }
}

function ajustarFuenteLectura(delta) {
    readFontSize = Math.min(22, Math.max(14, readFontSize + delta * 2));
    const hoja = document.getElementById('read-hoja-texto');
    if (hoja) hoja.style.fontSize = readFontSize + 'px';
}

function temaLectura(tema) {
    const hoja = document.getElementById('read-hoja-texto');
    if (!hoja) return;
    if (tema === 'sepia') {
        hoja.className = 'mt-4 max-w-3xl mx-auto rounded-2xl bg-[#F4ECD8] text-amber-950 border border-amber-300 p-6 sm:p-8 leading-relaxed transition-colors duration-300 space-y-4';
    } else if (tema === 'claro') {
        hoja.className = 'mt-4 max-w-3xl mx-auto rounded-2xl bg-white text-slate-900 border border-slate-200 p-6 sm:p-8 leading-relaxed transition-colors duration-300 space-y-4';
    } else {
        hoja.className = 'mt-4 max-w-3xl mx-auto rounded-2xl bg-slate-900 text-slate-100 border border-slate-800 p-6 sm:p-8 leading-relaxed transition-colors duration-300 space-y-4';
    }
}
</script>

@endsection
