@extends('layouts.app')

@section('title', 'Mi Estantería · NOVAETHER™')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-white">❤️ Mi Estantería Personal</h1>
        <p class="text-sm text-slate-300">Tus libros, cómics y mangas guardados para leer o reservar después.</p>
    </div>
    <a href="{{ route('books.index') }}" class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition">
        📚 Explorar más títulos
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @forelse($books as $book)
        <x-book-card :book="$book" :esFavorito="true" />
    @empty
        <div class="col-span-3 bg-slate-900 rounded-3xl border border-slate-800 p-12 text-center space-y-3">
            <div class="text-5xl">🤍</div>
            <h2 class="text-lg font-extrabold text-white">Tu estantería está vacía por ahora</h2>
            <p class="text-sm text-slate-300 max-w-md mx-auto">Haz clic en el corazón de cualquier libro, cómic o manga del catálogo para armar tu lista de lectura personal.</p>
            <a href="{{ route('books.index') }}" class="inline-block mt-2 bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black px-5 py-2.5 rounded-xl transition">
                Explorar Catálogo →
            </a>
        </div>
    @endforelse
</div>

<div class="mt-8">
    {{ $books->links() }}
</div>
@endsection
