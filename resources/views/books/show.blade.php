@extends('layouts.app')

@section('title', $book->titulo . ' · NOVAETHER™')

@section('content')

<div class="mb-5 flex items-center justify-between">
    <a href="{{ route('books.index') }}" class="inline-flex items-center gap-2 text-xs font-extrabold uppercase tracking-wider text-amber-400 hover:text-amber-300 transition">
        ← Volver al Catálogo Global
    </a>

    @auth
        @if(auth()->user()->esAdmin())
            <a href="{{ route('books.edit', $book) }}" class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white text-xs font-bold px-4 py-2 rounded-xl transition">
                ✏️ Editar Obra
            </a>
        @endif
    @endauth
</div>

<!-- FICHA PRINCIPAL DARK LUXURY -->
<div class="bg-[#0E1526]/95 rounded-3xl border border-slate-800 shadow-2xl overflow-hidden mb-8">
    <div class="grid grid-cols-1 lg:grid-cols-12">
        <!-- Columna Izquierda: Portada 3D -->
        <div class="lg:col-span-4 bg-gradient-to-br from-[#070B14] via-[#0D1326] to-[#150E28] p-8 flex flex-col items-center justify-center text-white border-b lg:border-b-0 lg:border-r border-slate-800">
            <div class="w-52 sm:w-60 aspect-[2/3] rounded-2xl overflow-hidden shadow-2xl border-2 border-amber-400/30 bg-slate-900 flex items-center justify-center">
                @if($book->portada_url)
                    <img src="{{ $book->portada_url }}" alt="{{ $book->titulo }}" class="w-full h-full object-cover"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="hidden w-full h-full items-center justify-center text-6xl bg-slate-900">📚</div>
                @else
                    <span class="text-6xl">📚</span>
                @endif
            </div>

            <div class="mt-5 text-center">
                <span class="inline-block px-3.5 py-1 rounded-full text-xs font-black bg-amber-400 text-slate-950">
                    {{ $book->category->icono }} {{ $book->category->nombre }}
                </span>
                <p class="text-xs text-slate-400 mt-2 font-mono">ISBN: {{ $book->isbn }}</p>
            </div>
        </div>

        <!-- Columna Derecha: Información Completa + Acciones -->
        <div class="lg:col-span-8 p-6 sm:p-10 flex flex-col justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2 mb-3">
                    @if($book->estaDisponible())
                        <span class="px-3 py-1 rounded-full text-xs font-black bg-emerald-500/20 text-emerald-300 border border-emerald-500/40">
                            🟢 EN STOCK ({{ $book->stock_disponible }} de {{ $book->stock_total }} unidades disponibles)
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs font-black bg-rose-500/20 text-rose-300 border border-rose-500/40">
                            🔴 AGOTADO TEMPORALMENTE (0 de {{ $book->stock_total }} disponibles)
                        </span>
                    @endif

                    <span class="px-3 py-1 rounded-full text-xs font-extrabold uppercase bg-cyan-500/15 text-cyan-300 border border-cyan-500/30">
                        Formato: {{ $book->formato }}
                    </span>
                </div>

                <h1 class="font-serif-editorial text-2xl sm:text-4xl font-extrabold text-white leading-tight">
                    {{ $book->titulo }}
                </h1>
                <p class="text-base sm:text-lg font-bold text-slate-400 mt-1">
                    ✍️ Autor: <span class="text-amber-300">{{ $book->autor }}</span>
                </p>

                <!-- Sinopsis -->
                <div class="mt-5 bg-[#090D18] rounded-2xl p-4 border border-slate-800">
                    <h2 class="text-xs font-extrabold uppercase tracking-wider text-amber-400 mb-1">Sinopsis Oficial</h2>
                    <p class="text-sm text-slate-300 leading-relaxed">
                        {{ $book->sinopsis ?? 'Obra disponible en el catálogo oficial de NOVAETHER™.' }}
                    </p>
                </div>

                <!-- Ficha Técnica -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5">
                    <div class="bg-[#090D18] p-3.5 rounded-xl border border-slate-800">
                        <span class="block text-[10px] font-extrabold uppercase text-slate-500">Editorial</span>
                        <span class="text-xs font-bold text-slate-200">{{ $book->editorial ?? 'General' }}</span>
                    </div>
                    <div class="bg-[#090D18] p-3.5 rounded-xl border border-slate-800">
                        <span class="block text-[10px] font-extrabold uppercase text-slate-500">Año Publicación</span>
                        <span class="text-xs font-bold text-slate-200">{{ $book->anio_publicacion ?? 'Clásico' }}</span>
                    </div>
                    <div class="bg-[#090D18] p-3.5 rounded-xl border border-slate-800">
                        <span class="block text-[10px] font-extrabold uppercase text-slate-500">Bóveda / Ubicación</span>
                        <span class="text-xs font-bold text-slate-200">{{ $book->ubicacion_estante ?? 'Catálogo General' }}</span>
                    </div>
                    <div class="bg-[#090D18] p-3.5 rounded-xl border border-slate-800">
                        <span class="block text-[10px] font-extrabold uppercase text-slate-500">Disponibilidad</span>
                        <span class="text-xs font-black {{ $book->estaDisponible() ? 'text-emerald-400' : 'text-rose-400' }}">
                            {{ $book->stock_disponible }} / {{ $book->stock_total }} Uds.
                        </span>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción Principal -->
            <div class="mt-8 pt-5 border-t border-slate-800 flex flex-wrap items-center gap-3">
                @if($book->estaDisponible())
                    <a href="{{ route('loans.create', ['book_id' => $book->id]) }}"
                       class="bg-gradient-to-r from-amber-400 via-orange-500 to-rose-500 hover:from-amber-300 hover:to-rose-400 text-slate-950 font-black text-sm px-6 py-3 rounded-xl shadow-lg shadow-amber-500/20 transition">
                        🔥 Reservar / Solicitar Préstamo
                    </a>
                @else
                    <a href="{{ route('loans.create', ['book_id' => $book->id]) }}"
                       class="bg-rose-600 hover:bg-rose-500 text-white font-extrabold text-sm px-6 py-3 rounded-xl shadow transition">
                        🚫 Sin Stock Disponible (Probar Bloqueo)
                    </a>
                @endif

                <button type="button" onclick="document.getElementById('visor-virtual').scrollIntoView({behavior: 'smooth'})"
                        class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white font-bold text-sm px-5 py-3 rounded-xl transition">
                    📖 Abrir Visor Cloud Reader
                </button>

                @if($book->archivo_pdf)
                    <a href="{{ $book->archivo_pdf }}" target="_blank"
                       class="bg-cyan-500/15 hover:bg-cyan-500/25 text-cyan-300 border border-cyan-500/30 font-bold text-sm px-4 py-3 rounded-xl transition">
                        🌐 Edición Externa ↗
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- VISOR DE LECTURA VIRTUAL INTEGRADO -->
<div id="visor-virtual" class="rounded-3xl border border-slate-800 bg-[#090D18] text-slate-100 p-6 sm:p-8 shadow-2xl mb-10">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-5 border-b border-slate-800">
        <div class="flex items-center gap-3">
            <span class="text-2xl">🖥️</span>
            <div>
                <h2 class="text-base font-extrabold text-amber-400">NOVAETHER™ Cloud Reader</h2>
                <p class="text-xs text-slate-400">Lectura previa interactiva de «{{ $book->titulo }}»</p>
            </div>
        </div>

        <div class="flex items-center gap-2 text-xs">
            <span class="text-slate-400 font-semibold">Tema de lectura:</span>
            <button type="button" onclick="cambiarTemaVisor('oscuro')" class="px-3 py-1.5 rounded-lg bg-slate-800 text-white font-bold border border-slate-700">🌙 Nocturno</button>
            <button type="button" onclick="cambiarTemaVisor('sepia')" class="px-3 py-1.5 rounded-lg bg-[#F4ECD8] text-amber-950 font-bold">📜 Sepia</button>
            <button type="button" onclick="cambiarTemaVisor('claro')" class="px-3 py-1.5 rounded-lg bg-white text-slate-900 font-bold">☀️ Papel</button>
        </div>
    </div>

    <div id="hoja-lectura" class="mt-6 max-w-3xl mx-auto rounded-2xl bg-[#0E1526] border border-slate-800 p-6 sm:p-10 leading-relaxed transition-colors duration-300">
        <p class="text-xs uppercase tracking-widest text-amber-400 font-bold mb-2">{{ $book->category->nombre }} · Edición Oficial</p>
        <h3 class="font-serif-editorial text-2xl font-bold mb-1">{{ $book->titulo }}</h3>
        <p class="text-xs opacity-75 mb-6">Por {{ $book->autor }} · {{ $book->editorial }} ({{ $book->anio_publicacion }})</p>

        <div class="space-y-4 text-sm sm:text-base leading-loose">
            <p>{{ $book->sinopsis }}</p>
            <p>
                Esta obra forma parte de la bóveda editorial de <strong>NOVAETHER™</strong>.
                @if($book->estaDisponible())
                    Actualmente contamos con <strong>{{ $book->stock_disponible }} unidad(es) disponible(s)</strong> en <em>{{ $book->ubicacion_estante }}</em> para reserva o lectura inmediata.
                @else
                    Actualmente todas las unidades ({{ $book->stock_total }}) se encuentran reservadas. Nuestro sistema protege automáticamente el inventario bloqueando nuevas salidas hasta que se registre una devolución.
                @endif
            </p>
        </div>
    </div>
</div>

<!-- OBRAS RELACIONADAS -->
@if($relacionados->isNotEmpty())
<div>
    <h2 class="text-lg font-extrabold text-white mb-4">✨ Más títulos en {{ $book->category->icono }} {{ $book->category->nombre }}</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($relacionados as $rel)
            <a href="{{ route('books.show', $rel) }}" class="bg-[#0E1526] rounded-2xl border border-slate-800 hover:border-amber-400/50 p-4 transition flex gap-3 shadow-lg">
                <div class="w-16 h-24 rounded-lg overflow-hidden bg-slate-900 flex-shrink-0">
                    @if($rel->portada_url)
                        <img src="{{ $rel->portada_url }}" alt="{{ $rel->titulo }}" class="w-full h-full object-cover">
                    @endif
                </div>
                <div class="min-w-0">
                    <h3 class="font-bold text-sm text-white line-clamp-2">{{ $rel->titulo }}</h3>
                    <p class="text-xs text-slate-400 mt-0.5 truncate">{{ $rel->autor }}</p>
                    <span class="inline-block mt-2 text-[10px] font-black px-2 py-0.5 rounded {{ $rel->estaDisponible() ? 'bg-emerald-500/20 text-emerald-300' : 'bg-rose-500/20 text-rose-300' }}">
                        {{ $rel->estaDisponible() ? $rel->stock_disponible . ' disp.' : 'Agotado' }}
                    </span>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endif

<script>
function cambiarTemaVisor(tema) {
    const hoja = document.getElementById('hoja-lectura');
    if (tema === 'sepia') {
        hoja.className = 'mt-6 max-w-3xl mx-auto rounded-2xl bg-[#F4ECD8] text-amber-950 border border-amber-300 p-6 sm:p-10 leading-relaxed transition-colors duration-300';
    } else if (tema === 'claro') {
        hoja.className = 'mt-6 max-w-3xl mx-auto rounded-2xl bg-white text-slate-900 border border-slate-200 p-6 sm:p-10 leading-relaxed transition-colors duration-300';
    } else {
        hoja.className = 'mt-6 max-w-3xl mx-auto rounded-2xl bg-[#0E1526] text-slate-100 border border-slate-800 p-6 sm:p-10 leading-relaxed transition-colors duration-300';
    }
}
</script>

@endsection
