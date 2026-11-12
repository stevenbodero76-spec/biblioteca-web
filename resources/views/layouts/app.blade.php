<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'NOVAETHER™ · Books, Manga & Digital Vault')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #070B14;
            background-image:
                radial-gradient(at 12% 8%, rgba(245, 158, 11, 0.10) 0px, transparent 45%),
                radial-gradient(at 88% 15%, rgba(6, 182, 212, 0.10) 0px, transparent 45%),
                radial-gradient(at 50% 85%, rgba(244, 63, 94, 0.08) 0px, transparent 50%);
            background-attachment: fixed;
        }
        .font-serif-editorial { font-family: 'Playfair Display', serif; }
        .book-card-3d {
            transition: all 0.32s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .book-card-3d:hover {
            transform: translateY(-8px);
            box-shadow: 0 25px 50px -12px rgba(245, 158, 11, 0.18);
        }
        .cover-shine {
            transition: transform 0.4s ease;
        }
        .book-card-3d:hover .cover-shine {
            transform: scale(1.08) rotate(-1.2deg);
        }
    </style>
</head>
<body class="text-slate-100 min-h-screen flex flex-col selection:bg-amber-400 selection:text-slate-950">

    <!-- Cintillo comercial superior -->
    <div class="bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 text-slate-950 text-xs font-black py-2 px-4 text-center tracking-wide shadow-md">
        🔥 NOVAETHER™ STORE & DIGITAL VAULT · Crea tu cuenta gratis en 10 segundos y accede a Libros, Cómics, Manga y Lectura Cloud HD
    </div>

    <!-- Barra de navegación principal -->
    <header class="bg-[#070B14]/90 backdrop-blur-xl text-white sticky top-0 z-40 border-b border-slate-800/90 shadow-2xl">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20 gap-4">
                <!-- Logo Comercial Vectorial Único (NOVAETHER) -->
                <div class="flex items-center gap-7">
                    <a href="{{ route('home') }}" class="flex items-center gap-3.5 group">
                        <div class="relative w-12 h-12 rounded-2xl bg-gradient-to-br from-amber-400 via-orange-500 to-rose-500 p-[2px] shadow-lg shadow-amber-500/25 group-hover:scale-105 transition duration-300">
                            <div class="w-full h-full bg-[#090D1A] rounded-[14px] flex items-center justify-center">
                                <svg class="w-7 h-7" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <defs>
                                        <linearGradient id="novaGrad" x1="0" y1="0" x2="36" y2="36" gradientUnits="userSpaceOnUse">
                                            <stop offset="0%" stop-color="#FBBF24"/>
                                            <stop offset="55%" stop-color="#FB7185"/>
                                            <stop offset="100%" stop-color="#38BDF8"/>
                                        </linearGradient>
                                    </defs>
                                    <path d="M18 8C13.5 5.5 7.5 5.5 4 7.5V27.5C7.5 25.5 13.5 25.5 18 28C22.5 25.5 28.5 25.5 32 27.5V7.5C28.5 5.5 22.5 5.5 18 8Z" stroke="url(#novaGrad)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M18 8V28" stroke="url(#novaGrad)" stroke-width="2.4" stroke-linecap="round"/>
                                    <circle cx="18" cy="4.5" r="2" fill="#FBBF24"/>
                                    <path d="M9 13H14M9 17.5H14M22 13H27M22 17.5H27" stroke="#F8FAFC" stroke-width="1.8" stroke-linecap="round"/>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-extrabold text-xl tracking-tight bg-gradient-to-r from-white via-amber-200 to-orange-400 bg-clip-text text-transparent">
                                    NOVAETHER
                                </span>
                                <span class="text-[9px] font-black uppercase px-1.5 py-0.5 rounded bg-amber-400 text-slate-950">
                                    STORE
                                </span>
                            </div>
                            <span class="block text-[10px] text-slate-400 font-semibold uppercase tracking-[0.2em] -mt-0.5">
                                Books · Manga · Cloud Vault
                            </span>
                        </div>
                    </a>

                    <!-- Enlaces del Escaparate Comercial -->
                    <nav class="hidden lg:flex items-center gap-1.5">
                        <a href="{{ route('books.index') }}"
                           class="px-3.5 py-2 rounded-xl text-xs font-extrabold uppercase tracking-wider transition {{ request()->routeIs('home') || (request()->routeIs('books.index') && !request('category_id') && !request('buscar') && !request('formato')) ? 'bg-amber-400 text-slate-950 shadow-md shadow-amber-500/20' : 'text-slate-300 hover:bg-slate-800/80 hover:text-white' }}">
                            🔥 Catálogo Global
                        </a>
                        <a href="{{ route('books.index', ['buscar' => 'Manga']) }}"
                           class="px-3.5 py-2 rounded-xl text-xs font-extrabold uppercase tracking-wider text-rose-300 hover:bg-slate-800/80 transition">
                            🎌 Manga & Cómics
                        </a>
                        <a href="{{ route('books.index', ['formato' => 'digital']) }}"
                           class="px-3.5 py-2 rounded-xl text-xs font-extrabold uppercase tracking-wider text-cyan-300 hover:bg-slate-800/80 transition">
                            ⚡ Lectura Cloud
                        </a>

                        @auth
                            <span class="h-5 w-[1px] bg-slate-800 mx-1"></span>
                            @if(auth()->user()->esStaff())
                                <a href="{{ route('dashboard') }}"
                                   class="px-3.5 py-2 rounded-xl text-xs font-extrabold uppercase tracking-wider transition {{ request()->routeIs('dashboard') ? 'bg-emerald-500 text-slate-950' : 'text-emerald-300 hover:bg-slate-800 hover:text-white' }}">
                                    📊 Panel & Reporte
                                </a>
                                <a href="{{ route('readers.index') }}"
                                   class="px-3.5 py-2 rounded-xl text-xs font-extrabold uppercase tracking-wider transition {{ request()->routeIs('readers.*') ? 'bg-emerald-500 text-slate-950' : 'text-emerald-300 hover:bg-slate-800 hover:text-white' }}">
                                    👥 Clientes
                                </a>
                            @endif
                            <a href="{{ route('loans.index') }}"
                               class="px-3.5 py-2 rounded-xl text-xs font-extrabold uppercase tracking-wider transition {{ request()->routeIs('loans.*') ? 'bg-amber-400 text-slate-950' : 'text-amber-300 hover:bg-slate-800 hover:text-white' }}">
                                🔄 {{ auth()->user()->esCliente() ? 'Mis Préstamos' : 'Reservas & Préstamos' }}
                            </a>
                        @endauth
                    </nav>
                </div>

                <!-- Zona Derecha: Botones de Crear Cuenta / Iniciar Sesión / Perfil -->
                <div class="flex items-center gap-2.5">
                    @auth
                        <div class="text-right hidden sm:block">
                            <p class="text-xs font-bold text-white leading-none">{{ auth()->user()->name }}</p>
                            <span class="inline-block mt-1 px-2.5 py-0.5 text-[10px] font-black uppercase rounded-full {{ auth()->user()->esAdmin() ? 'bg-amber-400 text-slate-950' : (auth()->user()->esCliente() ? 'bg-cyan-400 text-slate-950' : 'bg-emerald-400 text-slate-950') }}">
                                {{ auth()->user()->rol }}
                            </span>
                        </div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-rose-600 hover:bg-rose-500 text-white text-xs font-extrabold px-4 py-2.5 rounded-xl transition shadow-lg">
                                Salir
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}"
                           class="bg-slate-900 hover:bg-slate-800 text-white border border-slate-700 text-xs font-extrabold px-4 py-2.5 rounded-xl transition">
                            🔐 Iniciar Sesión
                        </a>
                        <a href="{{ route('register') }}"
                           class="bg-gradient-to-r from-amber-400 via-orange-500 to-rose-500 hover:from-amber-300 hover:to-rose-400 text-slate-950 text-xs font-black px-4 py-2.5 rounded-xl shadow-lg shadow-amber-500/25 transition flex items-center gap-1.5">
                            ✨ Crear Cuenta Gratis
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Mensajes Flash -->
    <div class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        @if(session('success'))
            <div class="rounded-2xl bg-emerald-950/90 text-emerald-100 border border-emerald-500/60 px-4 py-3.5 flex items-center justify-between shadow-xl">
                <div class="flex items-center gap-2.5">
                    <span class="text-lg">✅</span>
                    <span class="font-semibold text-sm">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-2xl bg-rose-950/90 text-rose-100 border border-rose-500/60 px-4 py-3.5 flex items-center justify-between shadow-xl">
                <div class="flex items-center gap-2.5">
                    <span class="text-lg">⛔</span>
                    <span class="font-semibold text-sm">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-2xl bg-rose-950/90 border-2 border-rose-500 p-4 text-rose-100 shadow-xl">
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
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        @yield('content')
    </main>

    <!-- Footer Comercial -->
    <footer class="bg-[#050811] text-slate-400 border-t border-slate-800/80 mt-16 py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
            <div class="flex items-center gap-3">
                <span class="font-extrabold text-sm bg-gradient-to-r from-amber-300 to-rose-400 bg-clip-text text-transparent">NOVAETHER™</span>
                <span>· Plataforma Comercial de Libros, Cómics, Manga y Lectura Cloud</span>
            </div>
            <div class="text-slate-500">
                © {{ date('Y') }} NovaEther Digital Vault · Conectado en tiempo real a MySQL (XAMPP)
            </div>
        </div>
    </footer>

</body>
</html>
