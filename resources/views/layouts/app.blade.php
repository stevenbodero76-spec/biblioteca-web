<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#090D16">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="/manifest.webmanifest">
    <title>@yield('title', 'NOVAETHER™ · Books, Manga & Digital Vault')</title>
    @yield('meta')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="text-slate-100 min-h-screen flex flex-col selection:bg-amber-400 selection:text-slate-950">

    <!-- Cintillo superior -->
    <div class="no-print bg-linear-to-r from-amber-500 via-amber-400 to-orange-500 text-slate-950 text-xs font-extrabold py-2 px-4 text-center tracking-wide shadow-sm">
        Biblioteca Pública &amp; Bóveda Digital 100% Gratuita
    </div>

    <!-- Barra de navegación principal -->
    <header class="no-print bg-[#090D16]/95 backdrop-blur-xl text-white sticky top-0 z-40 border-b border-slate-800 shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20 gap-3">
                <!-- Logo Vectorial NOVAETHER -->
                <div class="flex items-center gap-5 min-w-0">
                    <a href="{{ route('home') }}" class="flex items-center gap-3 group shrink-0">
                        <div class="relative w-10 h-10 rounded-xl bg-linear-to-br from-amber-400 to-orange-500 p-0.5 shadow-lg shadow-amber-500/20 group-hover:scale-105 transition duration-300">
                            <div class="w-full h-full bg-[#090D16] rounded-[10px] flex items-center justify-center">
                                <svg class="w-5 h-5" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                    <path d="M18 8C13.5 5.5 7.5 5.5 4 7.5V27.5C7.5 25.5 13.5 25.5 18 28C22.5 25.5 28.5 25.5 32 27.5V7.5C28.5 5.5 22.5 5.5 18 8Z" stroke="#FBBF24" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M18 8V28" stroke="#F59E0B" stroke-width="2.4" stroke-linecap="round"/>
                                    <circle cx="18" cy="4.5" r="2" fill="#FBBF24"/>
                                    <path d="M9 13H14M9 17.5H14M22 13H27M22 17.5H27" stroke="#E2E8F0" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-extrabold text-lg tracking-tight text-white">
                                    NOVA<span class="text-amber-400">ETHER</span>
                                </span>
                                <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded bg-amber-400 text-slate-950">
                                    BIBLIOTECA
                                </span>
                            </div>
                            <span class="block text-[9px] text-slate-300 font-semibold uppercase tracking-[0.18em] -mt-0.5">
                                Books · Manga · Digital Vault
                            </span>
                        </div>
                    </a>

                    <!-- Navegación Desktop Limpia -->
                    <nav class="hidden lg:flex items-center gap-1" aria-label="Navegación principal">
                        <a href="{{ route('books.index') }}"
                           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold tracking-wide transition {{ request()->routeIs('home') || (request()->routeIs('books.index') && !request('category_id') && !request('formato')) ? 'bg-amber-400 text-slate-950 shadow-sm font-extrabold' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                            <span>Catálogo</span>
                        </a>

                        <!-- Menú desplegable de las 12 Categorías -->
                        <div class="relative group" id="nav-cat-wrapper">
                            <button type="button"
                                    onclick="document.getElementById('nav-cat-dropdown').classList.toggle('hidden')"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold tracking-wide transition {{ request('category_id') ? 'bg-amber-400/15 text-amber-300 border border-amber-400/30' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                                <span>Categorías</span>
                                <svg class="w-3.5 h-3.5 transition-transform group-hover:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div id="nav-cat-dropdown" class="absolute left-0 top-full pt-2 w-80 hidden group-hover:block z-50">
                                <div class="bg-slate-900 border border-slate-700/90 rounded-2xl shadow-2xl p-2 grid grid-cols-1 gap-0.5 max-h-96 overflow-y-auto">
                                    @foreach($navCategories ?? [] as $cat)
                                        <a href="{{ route('books.index', ['category_id' => $cat->id]) }}"
                                           class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-slate-200 hover:bg-slate-800 hover:text-amber-300 transition">
                                            <span>{{ $cat->icono }}</span>
                                            <span class="truncate">{{ $cat->nombre }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('books.index', ['formato' => 'digital']) }}"
                           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-bold tracking-wide transition {{ request('formato') === 'digital' ? 'bg-amber-400 text-slate-950 font-extrabold' : 'text-slate-200 hover:bg-slate-800 hover:text-white' }}">
                            <span>Edición Digital</span>
                        </a>

                        @auth
                            <span class="h-4 w-px bg-slate-700 mx-1"></span>

                            <a href="{{ route('favorites.index') }}"
                               class="px-2.5 py-2 rounded-xl text-xs font-bold tracking-wide transition {{ request()->routeIs('favorites.*') ? 'bg-rose-500 text-white font-extrabold' : 'text-rose-300 hover:bg-slate-800' }}">
                                Estantería
                            </a>

                            <a href="{{ route('loans.index') }}"
                               class="px-2.5 py-2 rounded-xl text-xs font-bold tracking-wide transition {{ request()->routeIs('loans.*') ? 'bg-amber-400 text-slate-950 font-extrabold' : 'text-amber-300 hover:bg-slate-800' }}">
                                {{ auth()->user()->esCliente() ? 'Tickets QR' : 'Préstamos' }}
                            </a>

                            @if(auth()->user()->esAdmin())
                                <div class="relative group">
                                    <button type="button"
                                            onclick="document.getElementById('nav-admin-dropdown').classList.toggle('hidden')"
                                            class="inline-flex items-center gap-1 px-2.5 py-2 rounded-xl text-xs font-extrabold tracking-wide transition {{ request()->routeIs('dashboard', 'readers.*', 'admin.*') ? 'bg-emerald-500 text-slate-950' : 'text-emerald-300 hover:bg-slate-800' }}">
                                        <span>Admin</span>
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" /></svg>
                                    </button>
                                    <div id="nav-admin-dropdown" class="absolute right-0 top-full pt-2 w-56 hidden group-hover:block z-50">
                                        <div class="bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl p-2 space-y-0.5">
                                            <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-xl text-xs font-bold text-slate-200 hover:bg-slate-800 hover:text-emerald-300">📊 Dashboard Ejecutivo</a>
                                            <a href="{{ route('readers.index') }}" class="block px-3 py-2 rounded-xl text-xs font-bold text-slate-200 hover:bg-slate-800 hover:text-emerald-300">👥 Directorio de Lectores</a>
                                            <a href="{{ route('admin.complaints.index') }}" class="block px-3 py-2 rounded-xl text-xs font-bold text-slate-200 hover:bg-slate-800 hover:text-amber-300">📖 Reclamaciones</a>
                                            <a href="{{ route('admin.waitlists.index') }}" class="block px-3 py-2 rounded-xl text-xs font-bold text-slate-200 hover:bg-slate-800 hover:text-amber-300">🔔 Listas de Espera</a>
                                            <a href="{{ route('admin.reviews.index') }}" class="block px-3 py-2 rounded-xl text-xs font-bold text-slate-200 hover:bg-slate-800 hover:text-amber-300">💬 Moderar Reseñas</a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endauth
                    </nav>
                </div>

                <!-- Zona Derecha: Cuenta + Botón Móvil -->
                <div class="flex items-center gap-2">
                    @auth
                        <a href="{{ route('profile.edit') }}" class="text-right hidden sm:block group">
                            <p class="text-xs font-bold text-white leading-none group-hover:text-amber-300 transition">{{ auth()->user()->name }}</p>
                            <div class="flex items-center justify-end gap-1.5 mt-1">
                                @if(auth()->user()->reader)
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-800 text-amber-300 border border-amber-400/30">
                                        {{ auth()->user()->reader->puntos_karma }} pts
                                    </span>
                                @endif
                                <span class="px-2.5 py-0.5 text-[10px] font-black uppercase rounded-full {{ auth()->user()->esAdmin() ? 'bg-amber-400 text-slate-950' : 'bg-sky-400 text-slate-950' }}">
                                    {{ auth()->user()->esAdmin() ? 'Admin' : 'Cliente' }}
                                </span>
                            </div>
                        </a>

                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-rose-600 hover:bg-rose-500 text-white text-xs font-extrabold px-3 py-2 rounded-xl transition shadow">
                                Salir
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}"
                           class="bg-slate-900 hover:bg-slate-800 text-slate-100 border border-slate-700 text-xs font-bold px-4 py-2 rounded-xl transition">
                            Iniciar Sesión
                        </a>
                        <a href="{{ route('register') }}"
                           class="hidden sm:inline-block bg-linear-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 text-xs font-black px-4 py-2 rounded-xl shadow-lg shadow-amber-500/20 transition">
                            Crear Cuenta
                        </a>
                    @endauth

                    <!-- Botón Menú Móvil -->
                    <button type="button" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')"
                            class="lg:hidden p-2 rounded-xl bg-slate-900 border border-slate-700 text-slate-200 hover:text-white"
                            aria-label="Abrir menú">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Menú Desplegable Móvil -->
            <div id="mobile-menu" class="hidden lg:hidden pb-4 pt-2 border-t border-slate-800 space-y-1">
                <a href="{{ route('books.index') }}" class="block px-3 py-2 rounded-xl text-xs font-bold text-slate-200 hover:bg-slate-800">Catálogo Completo</a>
                <details class="px-3 py-1">
                    <summary class="cursor-pointer py-1.5 text-xs font-bold text-slate-200 hover:text-amber-300">Categorías (12 áreas)</summary>
                    <div class="pl-2 mt-1 space-y-1 max-h-60 overflow-y-auto">
                        @foreach($navCategories ?? [] as $cat)
                            <a href="{{ route('books.index', ['category_id' => $cat->id]) }}" class="block px-2.5 py-1.5 rounded-lg text-xs text-slate-300 hover:bg-slate-800 hover:text-amber-300">
                                {{ $cat->icono }} {{ $cat->nombre }}
                            </a>
                        @endforeach
                    </div>
                </details>
                <a href="{{ route('books.index', ['formato' => 'digital']) }}" class="block px-3 py-2 rounded-xl text-xs font-bold text-slate-200 hover:bg-slate-800">Edición Digital</a>
                @auth
                    <a href="{{ route('favorites.index') }}" class="block px-3 py-2 rounded-xl text-xs font-bold text-rose-300 hover:bg-slate-800">❤️ Mi Estantería</a>
                    <a href="{{ route('loans.index') }}" class="block px-3 py-2 rounded-xl text-xs font-bold text-amber-300 hover:bg-slate-800">🎫 Tickets QR y Reservas</a>
                    <a href="{{ route('profile.edit') }}" class="block px-3 py-2 rounded-xl text-xs font-bold text-slate-200 hover:bg-slate-800">👤 Mi Perfil y Karma</a>
                    @if(auth()->user()->esAdmin())
                        <div class="pt-2 mt-2 border-t border-slate-800 space-y-1">
                            <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-xl text-xs font-bold text-emerald-300 hover:bg-slate-800">📊 Panel Ejecutivo Admin</a>
                            <a href="{{ route('readers.index') }}" class="block px-3 py-2 rounded-xl text-xs font-bold text-emerald-300 hover:bg-slate-800">👥 Directorio de Lectores</a>
                            <a href="{{ route('admin.complaints.index') }}" class="block px-3 py-2 rounded-xl text-xs font-bold text-amber-300 hover:bg-slate-800">📖 Reclamaciones</a>
                            <a href="{{ route('admin.waitlists.index') }}" class="block px-3 py-2 rounded-xl text-xs font-bold text-amber-300 hover:bg-slate-800">🔔 Listas de Espera</a>
                            <a href="{{ route('admin.reviews.index') }}" class="block px-3 py-2 rounded-xl text-xs font-bold text-amber-300 hover:bg-slate-800">💬 Moderar Reseñas</a>
                        </div>
                    @endif
                @else
                    <a href="{{ route('register') }}" class="block px-3 py-2 rounded-xl text-xs font-black text-amber-400 hover:bg-slate-800">Crear Cuenta Gratis</a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Notificación automática de Lista de Espera -->
    @if(isset($notificacionesEspera) && $notificacionesEspera->isNotEmpty())
        <div class="no-print max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 mt-4 space-y-2">
            @foreach($notificacionesEspera as $alerta)
                <div class="rounded-2xl bg-emerald-950/95 border border-emerald-500/70 px-4 py-3 text-emerald-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-lg">
                    <div class="flex items-center gap-2.5 text-sm">
                        <span class="text-xl">🔔</span>
                        <div>
                            <strong class="text-emerald-300">¡Obra disponible en tu Lista de Espera!</strong>
                            <span class="block sm:inline text-xs sm:text-sm">«{{ $alerta->book->titulo }}» ya tiene ejemplar disponible.</span>
                        </div>
                    </div>
                    <a href="{{ route('loans.create', ['book_id' => $alerta->book_id]) }}"
                       class="self-start sm:self-auto bg-emerald-400 hover:bg-emerald-300 text-slate-950 text-xs font-black px-4 py-2 rounded-xl transition">
                        Reservar Ahora →
                    </a>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Mensajes Flash -->
    <div class="no-print max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 mt-4 space-y-3">
        @if(session('success'))
            <div role="status" class="js-auto-dismiss rounded-2xl bg-emerald-950/90 text-emerald-100 border border-emerald-500/60 px-4 py-3.5 flex items-center justify-between gap-3 shadow-xl transition-opacity duration-500">
                <div class="flex items-center gap-2.5">
                    <span class="text-lg">✅</span>
                    <span class="font-semibold text-sm">{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-300 hover:text-white text-xs font-bold px-2 py-1" aria-label="Cerrar">✕</button>
            </div>
        @endif

        @if(session('error'))
            <div role="alert" class="js-auto-dismiss rounded-2xl bg-rose-950/90 text-rose-100 border border-rose-500/60 px-4 py-3.5 flex items-center justify-between gap-3 shadow-xl transition-opacity duration-500">
                <div class="flex items-center gap-2.5">
                    <span class="text-lg">⛔</span>
                    <span class="font-semibold text-sm">{{ session('error') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-rose-300 hover:text-white text-xs font-bold px-2 py-1" aria-label="Cerrar">✕</button>
            </div>
        @endif

        @if($errors->any())
            <div role="alert" class="rounded-2xl bg-rose-950/95 border-2 border-rose-500 p-4 text-rose-100 shadow-xl">
                <p class="font-extrabold text-sm mb-1 text-rose-300">⚠️ Atención — Verifica los siguientes datos:</p>
                <ul class="list-disc list-inside text-sm font-medium space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <!-- Contenido principal -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-24 md:pb-8">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="no-print bg-[#060911] text-slate-300 border-t border-slate-800 mt-10 py-10 pb-24 md:pb-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-4 text-xs">
            <div class="flex items-center gap-3">
                <span class="font-extrabold text-sm text-amber-400">NOVAETHER™</span>
                <span class="text-slate-300">· Biblioteca Digital Pública &amp; Bóveda Abierta</span>
            </div>
            <div class="flex flex-wrap items-center gap-4">
                <a href="{{ route('complaints.create') }}" class="inline-flex items-center gap-1.5 text-slate-200 hover:text-amber-300 font-bold border border-slate-700 px-3 py-1.5 rounded-lg transition">
                    📖 Libro de Reclamaciones Virtual
                </a>
                <span class="text-slate-400">© {{ date('Y') }} NovaEther Digital Vault</span>
            </div>
        </div>
    </footer>

    <!-- Barra Inferior Táctil App / WebView (Solo Móvil) -->
    <nav class="no-print md:hidden fixed bottom-0 inset-x-0 z-40 bg-[#090D16]/95 backdrop-blur-xl border-t border-slate-800 px-2 py-1.5 grid grid-cols-4 text-[10px] font-bold" aria-label="Menú inferior móvil">
        <a href="{{ route('books.index') }}" class="flex flex-col items-center justify-center py-1 rounded-xl {{ request()->routeIs('home', 'books.index') ? 'text-amber-400' : 'text-slate-300' }}">
            <span class="text-base leading-none mb-0.5">📚</span>
            <span>Catálogo</span>
        </a>
        <a href="{{ auth()->check() ? route('favorites.index') : route('login') }}" class="flex flex-col items-center justify-center py-1 rounded-xl {{ request()->routeIs('favorites.*') ? 'text-rose-400' : 'text-slate-300' }}">
            <span class="text-base leading-none mb-0.5">❤️</span>
            <span>Estantería</span>
        </a>
        <a href="{{ auth()->check() ? route('loans.index') : route('login') }}" class="flex flex-col items-center justify-center py-1 rounded-xl {{ request()->routeIs('loans.*') ? 'text-amber-400' : 'text-slate-300' }}">
            <span class="text-base leading-none mb-0.5">🎫</span>
            <span>{{ auth()->check() && auth()->user()->esCliente() ? 'Tickets' : 'Préstamos' }}</span>
        </a>
        <a href="{{ auth()->check() ? (auth()->user()->esAdmin() ? route('dashboard') : route('profile.edit')) : route('login') }}" class="flex flex-col items-center justify-center py-1 rounded-xl {{ request()->routeIs('profile.*', 'dashboard') ? 'text-emerald-400' : 'text-slate-300' }}">
            <span class="text-base leading-none mb-0.5">{{ auth()->check() && auth()->user()->esAdmin() ? '📊' : '👤' }}</span>
            <span>{{ auth()->check() ? (auth()->user()->esAdmin() ? 'Admin' : 'Perfil') : 'Entrar' }}</span>
        </a>
    </nav>

    <!-- Modal de confirmación -->
    <div id="nova-confirm-modal" class="no-print fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/80 backdrop-blur-sm p-4" role="dialog" aria-modal="true" aria-labelledby="nova-confirm-title">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
            <div class="flex items-center gap-3">
                <span class="text-2xl">⚠️</span>
                <h3 id="nova-confirm-title" class="text-base font-extrabold text-white">Confirmar acción</h3>
            </div>
            <p id="nova-confirm-message" class="text-sm text-slate-300 leading-relaxed"></p>
            <div class="flex justify-end gap-2.5 pt-2">
                <button type="button" id="nova-confirm-cancel" class="px-4 py-2 rounded-xl border border-slate-700 text-xs font-bold text-slate-200 hover:bg-slate-800 transition">
                    Cancelar
                </button>
                <button type="button" id="nova-confirm-accept" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-xs font-extrabold text-white transition shadow">
                    Sí, continuar
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Vista Rápida (Quick View) -->
    <div id="nova-quickview-modal" class="no-print fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/85 backdrop-blur-sm p-4" role="dialog" aria-modal="true" aria-labelledby="qv-titulo">
        <div class="bg-slate-900 border border-slate-700 rounded-3xl max-w-2xl w-full overflow-hidden shadow-2xl relative">
            <button type="button" id="qv-close" class="absolute top-4 right-4 z-10 w-8 h-8 rounded-full bg-slate-950/80 border border-slate-700 text-slate-200 hover:text-white flex items-center justify-center text-xs font-black">✕</button>

            <div class="grid grid-cols-1 sm:grid-cols-12">
                <div class="sm:col-span-4 bg-slate-950 p-6 flex flex-col items-center justify-center border-b sm:border-b-0 sm:border-r border-slate-800">
                    <div class="w-36 aspect-2/3 rounded-xl overflow-hidden bg-slate-900 border border-slate-700 shadow-lg flex items-center justify-center">
                        <img id="qv-portada" src="" alt="" class="w-full h-full object-cover hidden">
                        <span id="qv-portada-fallback" class="text-4xl">📚</span>
                    </div>
                    <span id="qv-rating" class="mt-3 text-xs font-bold text-amber-400 text-center"></span>
                </div>

                <div class="sm:col-span-8 p-6 space-y-3">
                    <div class="flex flex-wrap items-center gap-2 pr-6">
                        <span id="qv-categoria" class="text-[10px] font-black uppercase px-2.5 py-0.5 rounded-full bg-amber-400/15 text-amber-300 border border-amber-400/30"></span>
                        <span id="qv-stock" class="text-[10px] font-black px-2.5 py-0.5 rounded-full"></span>
                        <span id="qv-idioma" class="text-[10px] font-black px-2.5 py-0.5 rounded-full bg-sky-500/15 text-sky-200 border border-sky-500/30"></span>
                        <span id="qv-licencia" class="text-[10px] font-black px-2.5 py-0.5 rounded-full bg-violet-500/15 text-violet-200 border border-violet-500/30"></span>
                    </div>

                    <h3 id="qv-titulo" class="font-serif-editorial text-2xl font-bold text-white leading-tight"></h3>
                    <p id="qv-autor" class="text-xs font-semibold text-slate-200"></p>

                    <div class="pt-1">
                        <span class="block text-[10px] uppercase font-bold text-slate-400">Formato</span>
                        <span id="qv-formato" class="text-xs font-bold text-slate-200"></span>
                    </div>

                    <p id="qv-sinopsis" class="text-xs text-slate-300 leading-relaxed max-h-28 overflow-y-auto"></p>

                    <div class="pt-3 flex flex-wrap gap-2 border-t border-slate-800">
                        <a id="qv-read-link" href="#" target="_blank" rel="noopener noreferrer" class="flex-1 text-center bg-linear-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 text-xs font-black px-4 py-2.5 rounded-xl transition shadow">
                            📖 Leer / Abrir
                        </a>
                        <a id="qv-show-link" href="#" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white text-xs font-bold transition">
                            Ver ficha →
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenedor de Toasts Flotantes -->
    <div id="nova-toast-container" class="no-print fixed bottom-20 md:bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none px-4 sm:px-0"></div>

    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>
</body>
</html>
