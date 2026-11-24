@extends('layouts.app')

@section('title', 'Gestión de Categorías · Admin NOVAETHER™')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-white">🗂️ Gestión de Categorías del Catálogo</h1>
        <p class="text-sm text-slate-300">Organiza las colecciones temáticas visibles en la navegación y filtros públicos.</p>
    </div>
    <a href="{{ route('dashboard') }}" class="text-xs font-extrabold uppercase text-amber-400 hover:underline">← Volver al Panel</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
    <!-- Crear nueva categoría -->
    <div class="lg:col-span-4 bg-slate-900/95 rounded-3xl border border-slate-800 p-6 shadow-xl h-fit">
        <h2 class="text-base font-extrabold text-white mb-4">➕ Nueva Categoría</h2>
        <form action="{{ route('admin.categories.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Nombre *</label>
                <input type="text" name="nombre" value="{{ old('nombre') }}" required placeholder="Ej. Ensayo y Crítica"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-400 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Icono / Emoji</label>
                <input type="text" name="icono" value="{{ old('icono', '📚') }}" maxlength="30"
                       class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-amber-400 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-extrabold uppercase text-slate-300 mb-1.5">Descripción</label>
                <textarea name="descripcion" rows="2" placeholder="Breve descripción para orientar a los lectores..."
                          class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white placeholder-slate-500 focus:border-amber-400 focus:outline-none">{{ old('descripcion') }}</textarea>
            </div>
            <button type="submit" class="w-full bg-linear-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 text-xs font-black py-3 rounded-xl shadow-lg transition">
                💾 Crear Categoría
            </button>
        </form>
    </div>

    <!-- Listado y edición rápida -->
    <div class="lg:col-span-8 space-y-3">
        @forelse($categories as $category)
            <div class="bg-slate-900/95 rounded-2xl border border-slate-800 p-4 sm:p-5 shadow-lg">
                <form action="{{ route('admin.categories.update', $category) }}" method="POST" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                        <div class="sm:col-span-2">
                            <label class="block text-[10px] font-black uppercase text-slate-400 mb-1">Icono</label>
                            <input type="text" name="icono" value="{{ $category->icono }}" maxlength="30"
                                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-center text-white">
                        </div>
                        <div class="sm:col-span-5">
                            <label class="block text-[10px] font-black uppercase text-slate-400 mb-1">Nombre *</label>
                            <input type="text" name="nombre" value="{{ $category->nombre }}" required
                                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                        </div>
                        <div class="sm:col-span-5">
                            <label class="block text-[10px] font-black uppercase text-slate-400 mb-1">Descripción</label>
                            <input type="text" name="descripcion" value="{{ $category->descripcion }}"
                                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs text-slate-200">
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-800/80">
                        <div class="flex items-center gap-2 text-xs">
                            <span class="px-2.5 py-1 rounded-full bg-slate-950 border border-slate-800 text-amber-300 font-bold">
                                📚 {{ $category->books_count }} obra(s)
                            </span>
                            <span class="px-2.5 py-1 rounded-full bg-slate-950 border border-slate-800 text-emerald-300 font-bold">
                                {{ $category->books_sum_stock_disponible ?? 0 }} disp.
                            </span>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="submit" class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white text-xs font-bold px-3.5 py-1.5 rounded-xl transition">
                                Guardar
                            </button>
                </form>

                            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST"
                                  data-confirm="¿Eliminar la categoría «{{ $category->nombre }}»?">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        @disabled($category->books_count > 0)
                                        class="px-3 py-1.5 rounded-xl text-xs font-bold border transition {{ $category->books_count > 0 ? 'bg-slate-950 border-slate-800 text-slate-500 cursor-not-allowed' : 'bg-rose-950 hover:bg-rose-800 text-rose-200 border-rose-500/40' }}"
                                        title="{{ $category->books_count > 0 ? 'Tiene obras asociadas' : 'Eliminar categoría' }}">
                                    🗑️ Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
            </div>
        @empty
            <div class="bg-slate-900 rounded-2xl border border-slate-800 p-8 text-center text-sm text-slate-300">
                No existen categorías registradas todavía.
            </div>
        @endforelse
    </div>
</div>
@endsection
