@extends('layouts.app')

@section('title', 'Reservar / Registrar Préstamo · NOVAETHER™')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-2xl font-extrabold text-white">🔥 Reservar Obra / Registrar Préstamo</h1>
            <p class="text-sm text-slate-400">Verificación automática de inventario disponible en tiempo real.</p>
        </div>
        <a href="{{ route('loans.index') }}" class="text-xs font-extrabold uppercase text-amber-400 hover:underline">← Ver Préstamos</a>
    </div>

    <div class="mb-5 bg-amber-400/10 border border-amber-400/30 rounded-2xl p-4 text-xs text-amber-200">
        <strong>⚡ Protección Inteligente de Inventario NOVAETHER™:</strong> El sistema verifica en tiempo real las unidades disponibles de cada obra y bloquea automáticamente préstamos de títulos agotados (Stock 0).
    </div>

    <form action="{{ route('loans.store') }}" method="POST" class="bg-[#0E1526] rounded-3xl border border-slate-800 p-6 sm:p-8 shadow-2xl space-y-4">
        @csrf

        <div>
            <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">1. Seleccionar Obra del Catálogo *</label>
            <select name="book_id" required class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
                <option value="">Seleccione un libro, cómic o manga...</option>
                @foreach($books as $book)
                    <option value="{{ $book->id }}"
                            @selected(old('book_id', $selectedBookId) == $book->id)>
                        {{ $book->estaDisponible() ? '🟢' : '🔴 [SIN STOCK]' }}
                        {{ $book->titulo }} — {{ $book->autor }}
                        (Disponibles: {{ $book->stock_disponible }}/{{ $book->stock_total }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">2. Cliente / Miembro Solicitante *</label>
            <select name="reader_id" required class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
                <option value="">Seleccione un cliente registrado...</option>
                @foreach($readers as $reader)
                    <option value="{{ $reader->id }}"
                            @selected(old('reader_id', $miPerfilReader?->id) == $reader->id)>
                        {{ $reader->estado === 'activo' ? '🟢' : '🔴 [SUSPENDIDO]' }}
                        {{ $reader->nombre_completo }} — Doc: {{ $reader->dni_codigo }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Fecha de Salida / Reserva *</label>
                <input type="date" name="fecha_prestamo" value="{{ old('fecha_prestamo', now()->toDateString()) }}" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>

            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Fecha Límite de Devolución *</label>
                <input type="date" name="fecha_devolucion_esperada" value="{{ old('fecha_devolucion_esperada', now()->addDays(7)->toDateString()) }}" required
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white">
            </div>
        </div>

        <div>
            <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Notas / Observaciones</label>
            <textarea name="observaciones" rows="2" placeholder="Ej. Entrega de edición física y activación de lectura Cloud."
                      class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-500">{{ old('observaciones') }}</textarea>
        </div>

        <div class="pt-3 flex justify-end gap-2">
            <a href="{{ route('loans.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-700 text-xs font-bold text-slate-300 hover:bg-slate-800">Cancelar</a>
            <button type="submit" class="bg-gradient-to-r from-amber-400 via-orange-500 to-rose-500 text-slate-950 text-xs font-black px-6 py-2.5 rounded-xl shadow-lg">
                Confirmar Reserva / Préstamo
            </button>
        </div>
    </form>
</div>
@endsection
