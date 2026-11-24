@php
    $stats = $adminSidebarStats ?? [
        'libros' => 0,
        'categorias' => 0,
        'prestamos_activos' => 0,
        'lectores' => 0,
        'espera' => 0,
        'reclamos' => 0,
        'resenas' => 0,
    ];

    $navItems = [
        [
            'route' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
            'icon' => '📊',
            'label' => 'Resumen General',
            'badge' => 'EN VIVO',
            'badge_class' => 'bg-emerald-500/15 text-emerald-300 border border-emerald-500/40 px-2.5 py-0.5 text-[10px] font-black',
        ],
        [
            'route' => route('admin.books.index'),
            'active' => request()->routeIs('admin.books.*', 'books.edit'),
            'icon' => '📚',
            'label' => 'Gestión de Libros',
            'badge' => (string) $stats['libros'],
            'badge_class' => 'w-7 h-7 rounded-full bg-slate-800/90 border border-slate-700 text-emerald-300 text-xs font-extrabold inline-flex items-center justify-center',
        ],
        [
            'route' => route('books.create'),
            'active' => request()->routeIs('books.create'),
            'icon' => '➕',
            'label' => 'Subir Nuevo Libro',
            'badge' => 'NUEVO',
            'badge_class' => 'bg-amber-400/15 text-amber-300 border border-amber-400/35 px-2 py-0.5 text-[10px] font-black',
        ],
        [
            'route' => route('admin.categories.index'),
            'active' => request()->routeIs('admin.categories.*'),
            'icon' => '🗂️',
            'label' => 'Categorías',
            'badge' => (string) $stats['categorias'],
            'badge_class' => 'w-7 h-7 rounded-full bg-slate-800/90 border border-slate-700 text-slate-200 text-xs font-bold inline-flex items-center justify-center',
        ],
        [
            'route' => route('loans.index'),
            'active' => request()->routeIs('loans.*'),
            'icon' => '🎫',
            'label' => 'Préstamos y Devoluciones',
            'badge' => (string) $stats['prestamos_activos'],
            'badge_class' => 'w-7 h-7 rounded-full bg-amber-500/15 border border-amber-400/40 text-amber-300 text-xs font-extrabold inline-flex items-center justify-center',
        ],
        [
            'route' => route('readers.index'),
            'active' => request()->routeIs('readers.*'),
            'icon' => '👥',
            'label' => 'Directorio de Lectores',
            'badge' => (string) $stats['lectores'],
            'badge_class' => 'w-7 h-7 rounded-full bg-slate-800/90 border border-slate-700 text-sky-300 text-xs font-bold inline-flex items-center justify-center',
        ],
        [
            'route' => route('admin.waitlists.index'),
            'active' => request()->routeIs('admin.waitlists.*'),
            'icon' => '🔔',
            'label' => 'Listas de Espera',
            'badge' => (string) $stats['espera'],
            'badge_class' => 'w-7 h-7 rounded-full bg-slate-800/90 border border-slate-700 text-amber-300 text-xs font-bold inline-flex items-center justify-center',
        ],
        [
            'route' => route('admin.complaints.index'),
            'active' => request()->routeIs('admin.complaints.*'),
            'icon' => '📖',
            'label' => 'Reclamaciones',
            'badge' => (string) $stats['reclamos'],
            'badge_class' => 'w-7 h-7 rounded-full bg-rose-500/15 border border-rose-400/40 text-rose-200 text-xs font-extrabold inline-flex items-center justify-center',
        ],
        [
            'route' => route('admin.reviews.index'),
            'active' => request()->routeIs('admin.reviews.*'),
            'icon' => '💬',
            'label' => 'Moderar Reseñas',
            'badge' => (string) $stats['resenas'],
            'badge_class' => 'w-7 h-7 rounded-full bg-slate-800/90 border border-slate-700 text-slate-200 text-xs font-bold inline-flex items-center justify-center',
        ],
        [
            'route' => route('profile.edit'),
            'active' => request()->routeIs('profile.*'),
            'icon' => '⚙️',
            'label' => 'Mi Perfil Admin',
            'dot_color' => 'bg-cyan-400',
        ],
    ];
@endphp

<aside id="admin-control-sidebar"
       class="no-print hidden lg:flex flex-col w-72 shrink-0 bg-[#080C15] border-r border-slate-800/90 min-h-screen justify-between shadow-2xl">
    <div class="p-4 space-y-5">
        <!-- Encabezado Panel Control -->
        <div class="relative text-center pt-2 pb-4 border-b border-slate-800/80 space-y-2">
            <button type="button"
                    onclick="const sb = document.getElementById('admin-control-sidebar'); sb.classList.add('hidden'); sb.classList.remove('flex', 'fixed', 'inset-y-0', 'left-0', 'z-50');"
                    class="lg:hidden absolute top-1 right-1 w-7 h-7 rounded-lg bg-slate-900 border border-slate-700 text-slate-300 hover:text-white text-xs font-black"
                    aria-label="Cerrar menú lateral admin">
                ✕
            </button>
            <a href="{{ route('dashboard') }}" class="inline-block">
                <span class="block text-[11px] font-black uppercase tracking-[0.26em] text-slate-400">NOVAETHER™</span>
                <span class="block text-xl font-black tracking-wide text-white mt-0.5">CONTROL</span>
            </a>

            <div class="flex flex-col items-center gap-1.5 pt-1">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-400/10 border border-amber-400/40 text-amber-300 text-[10px] font-black uppercase tracking-wider">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                    ADMIN BIBLIOTECA
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/35 text-emerald-300 text-[10px] font-extrabold uppercase tracking-wider">
                    📚 BÓVEDA ABIERTA
                </span>
            </div>
        </div>

        <!-- Enlaces principales del panel -->
        <nav class="space-y-1.5" aria-label="Menú lateral de administración">
            @foreach($navItems as $item)
                <a href="{{ $item['route'] }}"
                   class="group flex items-center justify-between gap-2.5 px-3.5 py-2.5 rounded-2xl transition border {{ $item['active'] ? 'bg-slate-800/90 border-slate-700/90 text-white shadow-lg' : 'border-transparent text-slate-300 hover:bg-slate-900 hover:text-white hover:border-slate-800' }}">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="text-base shrink-0">{{ $item['icon'] }}</span>
                        <span class="text-xs font-extrabold leading-tight truncate">{{ $item['label'] }}</span>
                    </div>

                    @if(!empty($item['badge']))
                        <span class="shrink-0 rounded-full {{ $item['badge_class'] }}">
                            {{ $item['badge'] }}
                        </span>
                    @elseif(!empty($item['dot_color']))
                        <span class="w-2 h-2 rounded-full shrink-0 {{ $item['dot_color'] }}"></span>
                    @endif
                </a>
            @endforeach
        </nav>
    </div>

    <!-- Pie del Sidebar -->
    <div class="p-4 border-t border-slate-800/80 space-y-2.5 bg-[#060911]">
        <a href="{{ route('books.index') }}"
           class="w-full flex items-center justify-center gap-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700 text-amber-300 text-xs font-extrabold py-2.5 px-3 transition">
            <span>🌐</span>
            <span>Ver Catálogo Público</span>
        </a>

        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit"
                    class="w-full flex items-center justify-center gap-2 rounded-xl bg-rose-950/60 hover:bg-rose-600 border border-rose-500/40 text-rose-200 hover:text-white text-xs font-black py-2.5 px-3 transition">
                <span>↪</span>
                <span>Salir</span>
            </button>
        </form>
    </div>
</aside>
