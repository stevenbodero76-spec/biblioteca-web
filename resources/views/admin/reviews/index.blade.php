@extends('layouts.app')

@section('title', 'Moderación de Reseñas · Admin NOVAETHER™')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-white">💬 Moderación de Reseñas y Comunidad</h1>
        <p class="text-sm text-slate-300">Oculta comentarios inadecuados o elimina spam manteniendo la transparencia de calificaciones.</p>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.reviews.index') }}" class="px-3 py-2 rounded-xl text-xs font-bold {{ ! $estado ? 'bg-amber-400 text-slate-950' : 'bg-slate-800 text-slate-200' }}">Todas</a>
        <a href="{{ route('admin.reviews.index', ['estado' => 'ocultas']) }}" class="px-3 py-2 rounded-xl text-xs font-bold {{ $estado === 'ocultas' ? 'bg-amber-400 text-slate-950' : 'bg-slate-800 text-slate-200' }}">Ocultas</a>
        <a href="{{ route('admin.reviews.index', ['estado' => 'spoilers']) }}" class="px-3 py-2 rounded-xl text-xs font-bold {{ $estado === 'spoilers' ? 'bg-amber-400 text-slate-950' : 'bg-slate-800 text-slate-200' }}">Con Spoiler</a>
    </div>
</div>

<div class="space-y-3">
    @forelse($reviews as $rev)
        <div class="bg-slate-900/95 rounded-2xl border {{ $rev->visible ? 'border-slate-800' : 'border-rose-500/40 bg-rose-950/20' }} p-5 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1.5 min-w-0">
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <a href="{{ route('books.show', $rev->book) }}" class="font-extrabold text-amber-300 hover:underline">{{ $rev->book->titulo }}</a>
                    <span class="text-slate-400">·</span>
                    <span class="font-bold text-white">{{ $rev->user->name }}</span>
                    <span class="text-amber-400 font-black">{{ str_repeat('★', $rev->rating) }}</span>
                    @if($rev->es_spoiler)
                        <span class="px-2 py-0.5 rounded bg-rose-500/20 text-rose-300 text-[10px] font-black uppercase">Spoiler</span>
                    @endif
                    @if(! $rev->visible)
                        <span class="px-2 py-0.5 rounded bg-rose-600 text-white text-[10px] font-black uppercase">Oculta al público</span>
                    @endif
                </div>
                <p class="text-sm text-slate-200">{{ $rev->comentario }}</p>
                <p class="text-[11px] text-slate-400">{{ $rev->created_at->format('d/m/Y H:i') }}</p>
            </div>

            <div class="flex items-center gap-2 flex-shrink-0">
                <form action="{{ route('reviews.visibility', $rev) }}" method="POST">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="px-3 py-2 rounded-xl text-xs font-bold {{ $rev->visible ? 'bg-slate-800 hover:bg-slate-700 text-slate-200' : 'bg-emerald-500 hover:bg-emerald-400 text-slate-950' }}">
                        {{ $rev->visible ? '🙈 Ocultar' : '👁️ Publicar' }}
                    </button>
                </form>

                <form action="{{ route('reviews.destroy', $rev) }}" method="POST" data-confirm="¿Eliminar permanentemente esta reseña?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-3 py-2 rounded-xl text-xs font-bold bg-rose-950 hover:bg-rose-800 text-rose-200 border border-rose-500/40">
                        🗑️ Eliminar
                    </button>
                </form>
            </div>
        </div>
    @empty
        <div class="bg-slate-900 rounded-2xl border border-slate-800 p-8 text-center text-sm text-slate-300">
            No hay reseñas en esta categoría.
        </div>
    @endforelse
</div>

<div class="mt-4">{{ $reviews->links() }}</div>
@endsection
