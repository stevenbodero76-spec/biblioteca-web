<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookRequest;
use App\Models\Book;
use App\Models\Category;
use App\Services\BookLinkInterconnectorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BookController extends Controller
{
    public function index(Request $request, BookLinkInterconnectorService $interconnector): View|JsonResponse
    {
        $filtros = $request->only(['buscar', 'category_id', 'formato', 'idioma', 'licencia', 'disponibilidad', 'orden']);

        $books = Book::with('category')
            ->withAvg(['reviews' => fn ($q) => $q->where('visible', true)], 'rating')
            ->withCount(['reviews' => fn ($q) => $q->where('visible', true)])
            ->filtro($filtros)
            ->ordenar($filtros['orden'] ?? null)
            ->paginate(12)
            ->withQueryString();

        $favoriteBookIds = Auth::check()
            ? Auth::user()->favorites()->pluck('book_id')->all()
            : [];

        $librosExternos = [];
        $terminoBusqueda = trim((string) ($filtros['buscar'] ?? ''));
        if ($terminoBusqueda !== '' && $books->total() === 0) {
            $librosExternos = $interconnector->buscarEnRedesExternas($terminoBusqueda, 6);
        }

        if ($request->ajax() || $request->boolean('partial')) {
            $html = view('books.partials.grid', compact('books', 'favoriteBookIds', 'filtros', 'librosExternos'))->render();

            return response()->json([
                'html' => $html,
                'total' => $books->total(),
                'total_externos' => count($librosExternos),
            ]);
        }

        $categoriesRaw = Cache::remember(
            'catalog_categories_with_count_v2',
            300,
            fn () => Category::withCount('books')->orderBy('id')->get()->toArray()
        );
        $categories = is_array($categoriesRaw)
            ? Category::hydrate($categoriesRaw)
            : Category::withCount('books')->orderBy('id')->get();

        $stats = Cache::remember('catalog_home_stats', 300, fn () => [
            'total_titulos' => (int) Book::count(),
            'total_ejemplares' => (int) Book::sum('stock_total'),
            'disponibles_ahora' => (int) Book::sum('stock_disponible'),
            'categorias_activas' => (int) Category::count(),
        ]);

        $showcaseTabsRaw = Cache::remember('catalog_showcase_tabs_v2', 300, fn () => [
            'valorados' => Book::withAvg(['reviews' => fn ($q) => $q->where('visible', true)], 'rating')
                ->withCount(['reviews' => fn ($q) => $q->where('visible', true)])
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('reviews_count')
                ->take(5)
                ->get()
                ->toArray(),
            'manga' => Book::withAvg(['reviews' => fn ($q) => $q->where('visible', true)], 'rating')
                ->withCount(['reviews' => fn ($q) => $q->where('visible', true)])
                ->whereHas('category', fn ($q) => $q->where('nombre', 'like', '%Cómics%'))
                ->take(5)
                ->get()
                ->toArray(),
            'tech' => Book::withAvg(['reviews' => fn ($q) => $q->where('visible', true)], 'rating')
                ->withCount(['reviews' => fn ($q) => $q->where('visible', true)])
                ->whereHas('category', fn ($q) => $q->where('nombre', 'like', '%Tecnología%')->orWhere('nombre', 'like', '%Negocios%')->orWhere('nombre', 'like', '%Desarrollo%'))
                ->take(5)
                ->get()
                ->toArray(),
        ]);

        $showcaseTabs = [
            'valorados' => Book::hydrate($showcaseTabsRaw['valorados'] ?? []),
            'manga' => Book::hydrate($showcaseTabsRaw['manga'] ?? []),
            'tech' => Book::hydrate($showcaseTabsRaw['tech'] ?? []),
        ];

        return view('books.index', compact(
            'books',
            'categories',
            'filtros',
            'stats',
            'showcaseTabs',
            'favoriteBookIds',
            'librosExternos'
        ));
    }

    public function show(Book $book, BookLinkInterconnectorService $interconnector): View
    {
        $esAdmin = Auth::check() && Auth::user()->esAdmin();

        $book->load([
            'category',
            'reviews' => function ($q) use ($esAdmin) {
                if (! $esAdmin) {
                    $q->where('visible', true);
                }
                $q->with('user')->latest();
            },
        ])
            ->loadAvg(['reviews' => fn ($q) => $q->where('visible', true)], 'rating')
            ->loadCount(['reviews' => fn ($q) => $q->where('visible', true)]);

        $enlacesLectura = $interconnector->resolverEnlacesLectura($book);
        if (! empty($enlacesLectura['embed_url'])) {
            $book->setAttribute('archivo_pdf', $enlacesLectura['embed_url']);
        } elseif (empty($book->archivo_pdf)) {
            $book->setAttribute('archivo_pdf', $enlacesLectura['principal_url']);
        }

        $relacionados = $interconnector->obtenerLibrosInterconectados($book, 4);

        $waitlistCount = $book->waitlists()
            ->whereIn('estado', ['en_espera', 'notificado'])
            ->count();

        $proximaDevolucion = ! $book->estaDisponible()
            ? $book->loans()
                ->whereIn('estado', ['prestado', 'vencido'])
                ->where('estado_pago', '!=', 'anulado')
                ->orderBy('fecha_devolucion_esperada')
                ->value('fecha_devolucion_esperada')
            : null;

        $miWaitlist = null;
        $miPosicionCola = null;
        $esFavorito = false;
        $miResena = null;

        if (Auth::check()) {
            $user = Auth::user();
            $esFavorito = $user->favorites()->where('book_id', $book->id)->exists();
            $miResena = $book->reviews()->where('user_id', $user->id)->first();

            if ($user->reader) {
                $miWaitlist = $book->waitlists()
                    ->where('reader_id', $user->reader->id)
                    ->whereIn('estado', ['en_espera', 'notificado'])
                    ->first();

                if ($miWaitlist) {
                    $miPosicionCola = $book->waitlists()
                        ->whereIn('estado', ['en_espera', 'notificado'])
                        ->where('id', '<=', $miWaitlist->id)
                        ->count();
                }
            }
        }

        return view('books.show', compact(
            'book',
            'relacionados',
            'enlacesLectura',
            'waitlistCount',
            'proximaDevolucion',
            'miWaitlist',
            'miPosicionCola',
            'esFavorito',
            'miResena'
        ));
    }

    public function download(Book $book, BookLinkInterconnectorService $interconnector): StreamedResponse|RedirectResponse
    {
        $archivo = trim((string) $book->archivo_pdf);

        if ($archivo !== '' && str_starts_with($archivo, '/storage/')) {
            $relativo = ltrim(substr($archivo, strlen('/storage/')), '/');
            if ($relativo !== '' && ! str_contains($relativo, '..') && Storage::disk('public')->exists($relativo)) {
                $nombreDescarga = (Str::slug($book->titulo) ?: 'obra-' . $book->id) . '.pdf';

                return Storage::disk('public')->download($relativo, $nombreDescarga);
            }
        }

        $enlaces = $interconnector->resolverEnlacesLectura($book);

        return redirect()->away($enlaces['principal_url']);
    }

    public function create(): View
    {
        $categories = Category::orderBy('nombre')->get();

        return view('books.create', compact('categories'));
    }

    public function store(BookRequest $request, BookLinkInterconnectorService $interconnector): RedirectResponse
    {
        $datos = $this->prepararDatosLibro($request, null, $interconnector);

        Book::create($datos);
        $this->limpiarCacheCatalogo();

        return redirect()
            ->route('books.index')
            ->with('success', 'Obra registrada e interconectada exitosamente en el catálogo.');
    }

    public function edit(Book $book): View
    {
        $categories = Category::orderBy('nombre')->get();
        $prestamosActivos = $book->prestamosActivosCount();

        return view('books.edit', compact('book', 'categories', 'prestamosActivos'));
    }

    public function update(BookRequest $request, Book $book, BookLinkInterconnectorService $interconnector): RedirectResponse
    {
        $datos = $this->prepararDatosLibro($request, $book, $interconnector);

        $book->update($datos);
        $this->limpiarCacheCatalogo();

        return redirect()
            ->route('books.index')
            ->with('success', 'Datos y enlaces de lectura de la obra actualizados correctamente.');
    }

    public function destroy(Book $book): RedirectResponse
    {
        if ($book->loans()->exists()) {
            return back()->with('error', 'No se puede eliminar esta obra porque tiene historial de préstamos asociados.');
        }

        $portada = $book->portada_url;
        $pdf = $book->archivo_pdf;
        $book->delete();
        $this->limpiarCacheCatalogo();

        $this->eliminarArchivoPublicoSiLocal($portada);
        $this->eliminarArchivoPublicoSiLocal($pdf);

        return redirect()
            ->route('books.index')
            ->with('success', 'Obra eliminada del catálogo.');
    }

    public function importarExterno(Request $request): RedirectResponse
    {
        if (! Auth::check() || ! Auth::user()->esAdmin()) {
            abort(403, 'Acción restringida a administradores.');
        }

        $datos = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'autor' => ['required', 'string', 'max:255'],
            'editorial' => ['nullable', 'string', 'max:255'],
            'anio_publicacion' => ['nullable', 'integer', 'min:1000', 'max:2100'],
            'isbn' => ['nullable', 'string', 'max:50'],
            'portada_url' => ['nullable', 'url', 'max:500'],
            'archivo_pdf' => ['required', 'url', 'max:500'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'idioma' => ['nullable', 'string', 'max:10'],
            'licencia' => ['nullable', 'in:dominio_publico,creative_commons,acceso_abierto'],
        ]);

        $categoriaId = $datos['category_id'] ?? Category::first()?->id;
        if (! $categoriaId) {
            $cat = Category::firstOrCreate(
                ['slug' => 'clasicos-dominio-publico'],
                ['nombre' => 'Clásicos y Dominio Público', 'icono' => '🏛️']
            );
            $categoriaId = $cat->id;
        }

        $isbn = trim((string) ($datos['isbn'] ?? ''));
        if ($isbn === '' || Book::where('isbn', $isbn)->exists()) {
            $isbn = 'EXT-' . strtoupper(Str::random(10));
        }

        $libro = Book::create([
            'category_id' => $categoriaId,
            'isbn' => $isbn,
            'titulo' => $datos['titulo'],
            'autor' => $datos['autor'],
            'editorial' => $datos['editorial'] ?: 'Red Pública Digital (Open Library / Gutenberg)',
            'anio_publicacion' => $datos['anio_publicacion'] ?? (int) date('Y'),
            'formato' => 'digital',
            'idioma' => $datos['idioma'] ?? 'es',
            'licencia' => $datos['licencia'] ?? 'dominio_publico',
            'sinopsis' => 'Obra importada desde la red abierta global (Open Library & Gutenberg). Disponible para lectura pública y visor embebido.',
            'portada_url' => $datos['portada_url'] ?? null,
            'archivo_pdf' => $datos['archivo_pdf'],
            'ubicacion_estante' => 'Bóveda Digital Abierta',
            'stock_total' => 10,
            'stock_disponible' => 10,
        ]);

        $this->limpiarCacheCatalogo();

        return redirect()
            ->route('books.show', $libro)
            ->with('success', '¡Obra «' . $libro->titulo . '» importada exitosamente al catálogo oficial de NOVAETHER™!');
    }

    public function buscarPorIsbn(string $isbn, BookLinkInterconnectorService $interconnector): JsonResponse
    {
        $cleanIsbn = preg_replace('/[^0-9Xx]/', '', $isbn);

        if (strlen($cleanIsbn) < 10) {
            return response()->json([
                'encontrado' => false,
                'mensaje' => 'Ingresa un ISBN válido (10 o 13 dígitos).',
            ], 422);
        }

        $cacheKey = "openlibrary_isbn_{$cleanIsbn}";
        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }

        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get('https://openlibrary.org/api/books', [
                    'bibkeys' => "ISBN:{$cleanIsbn}",
                    'format' => 'json',
                    'jscmd' => 'data',
                ]);

            if (! $response->successful()) {
                return response()->json([
                    'encontrado' => false,
                    'mensaje' => 'No se pudo consultar Open Library en este momento.',
                ], 502);
            }

            $payload = $response->json("ISBN:{$cleanIsbn}");

            if (! is_array($payload) || empty($payload)) {
                return response()->json([
                    'encontrado' => false,
                    'mensaje' => "No se encontró información para el ISBN {$cleanIsbn}.",
                ], 404);
            }

            $autores = collect($payload['authors'] ?? [])->pluck('name')->implode(', ');
            $editoriales = collect($payload['publishers'] ?? [])->pluck('name')->implode(', ');
            $anio = null;
            if (! empty($payload['publish_date']) && preg_match('/\b(1\d{3}|20\d{2})\b/', (string) $payload['publish_date'], $m)) {
                $anio = (int) $m[1];
            }

            $portada = $payload['cover']['large']
                ?? $payload['cover']['medium']
                ?? "https://covers.openlibrary.org/b/isbn/{$cleanIsbn}-L.jpg";

            $enlaces = $interconnector->extraerEnlacesDesdeOpenLibrary($cleanIsbn, $payload);

            $resultado = [
                'encontrado' => true,
                'titulo' => $payload['title'] ?? '',
                'autor' => $autores,
                'editorial' => $editoriales,
                'anio_publicacion' => $anio,
                'portada_url' => $portada,
                'archivo_pdf' => $enlaces['archivo_pdf'],
                'enlaces_lectura' => $enlaces['fuentes'],
                'sinopsis' => $payload['notes'] ?? ($payload['subtitle'] ?? ''),
            ];

            Cache::put($cacheKey, $resultado, now()->addDays(7));

            return response()->json($resultado);
        } catch (\Throwable) {
            return response()->json([
                'encontrado' => false,
                'mensaje' => 'Error de conexión al consultar Open Library.',
            ], 503);
        }
    }

    private function prepararDatosLibro(
        BookRequest $request,
        ?Book $book = null,
        ?BookLinkInterconnectorService $interconnector = null
    ): array {
        $datos = collect($request->validated())->except(['portada_file', 'pdf_file'])->toArray();

        if ($request->hasFile('portada_file')) {
            $path = $request->file('portada_file')->store('covers', 'public');
            $datos['portada_url'] = '/storage/' . ltrim($path, '/');
            if ($book) {
                $this->eliminarArchivoPublicoSiLocal($book->portada_url);
            }
        } elseif ($book && empty($datos['portada_url'])) {
            $datos['portada_url'] = $book->portada_url;
        }

        if ($request->hasFile('pdf_file')) {
            $path = $request->file('pdf_file')->store('pdfs', 'public');
            $datos['archivo_pdf'] = '/storage/' . ltrim($path, '/');
            if ($book) {
                $this->eliminarArchivoPublicoSiLocal($book->archivo_pdf);
            }
        } elseif ($book && empty($datos['archivo_pdf'])) {
            $datos['archivo_pdf'] = $book->archivo_pdf;
        }

        if (empty($datos['archivo_pdf']) && $interconnector) {
            $datos['archivo_pdf'] = $interconnector->construirUrlLecturaPorDefecto(
                (string) ($datos['isbn'] ?? ''),
                (string) ($datos['titulo'] ?? '')
            );
        }

        return $datos;
    }

    private function limpiarCacheCatalogo(): void
    {
        Cache::forget('catalog_categories_with_count_v2');
        Cache::forget('catalog_home_stats');
        Cache::forget('catalog_showcase_tabs_v2');
        Cache::forget('nav_categories_v2');
    }

    private function eliminarArchivoPublicoSiLocal(?string $ruta): void
    {
        if (! $ruta || ! str_starts_with($ruta, '/storage/')) {
            return;
        }

        $relativo = ltrim(substr($ruta, strlen('/storage/')), '/');
        if ($relativo !== '' && ! str_contains($relativo, '..')) {
            Storage::disk('public')->delete($relativo);
        }
    }
}
