<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookRequest;
use App\Models\Book;
use App\Models\Category;
use App\Services\BookLinkInterconnectorService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $filtros = $request->only(['buscar', 'category_id', 'formato', 'disponibilidad', 'orden']);

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

        if ($request->ajax() || $request->boolean('partial')) {
            $html = view('books.partials.grid', compact('books', 'favoriteBookIds', 'filtros'))->render();

            return response()->json([
                'html' => $html,
                'total' => $books->total(),
            ]);
        }

        $categories = Category::withCount('books')->orderBy('id')->get();

        $stats = [
            'total_titulos' => Book::count(),
            'total_ejemplares' => Book::sum('stock_total'),
            'disponibles_ahora' => Book::sum('stock_disponible'),
            'categorias_activas' => Category::count(),
        ];

        $showcaseTabs = [
            'valorados' => Book::with('category')
                ->withAvg(['reviews' => fn ($q) => $q->where('visible', true)], 'rating')
                ->withCount(['reviews' => fn ($q) => $q->where('visible', true)])
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('reviews_count')
                ->take(5)
                ->get(),
            'manga' => Book::with('category')
                ->withAvg(['reviews' => fn ($q) => $q->where('visible', true)], 'rating')
                ->withCount(['reviews' => fn ($q) => $q->where('visible', true)])
                ->whereHas('category', fn ($q) => $q->where('nombre', 'like', '%Cómics%'))
                ->take(5)
                ->get(),
            'tech' => Book::with('category')
                ->withAvg(['reviews' => fn ($q) => $q->where('visible', true)], 'rating')
                ->withCount(['reviews' => fn ($q) => $q->where('visible', true)])
                ->whereHas('category', fn ($q) => $q->where('nombre', 'like', '%Tecnología%')->orWhere('nombre', 'like', '%Negocios%')->orWhere('nombre', 'like', '%Desarrollo%'))
                ->take(5)
                ->get(),
        ];

        return view('books.index', compact(
            'books',
            'categories',
            'filtros',
            'stats',
            'showcaseTabs',
            'favoriteBookIds'
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
        if (empty($book->archivo_pdf)) {
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

    public function create(): View
    {
        $categories = Category::orderBy('nombre')->get();

        return view('books.create', compact('categories'));
    }

    public function store(BookRequest $request, BookLinkInterconnectorService $interconnector): RedirectResponse
    {
        $datos = $this->prepararDatosLibro($request, null, $interconnector);
        $datos['precio_alquiler'] = $datos['precio_alquiler'] ?? 0.00;
        $datos['precio_venta'] = $datos['precio_venta'] ?? 0.00;

        Book::create($datos);

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
        $datos['precio_alquiler'] = $datos['precio_alquiler'] ?? $book->precio_alquiler;
        $datos['precio_venta'] = $datos['precio_venta'] ?? $book->precio_venta;

        $book->update($datos);

        return redirect()
            ->route('books.index')
            ->with('success', 'Datos y enlaces de lectura de la obra actualizados correctamente.');
    }

    public function destroy(Book $book): RedirectResponse
    {
        if ($book->loans()->exists() || $book->orderItems()->exists()) {
            return back()->with('error', 'No se puede eliminar esta obra porque tiene historial de préstamos o ventas asociadas.');
        }

        $portada = $book->portada_url;
        $pdf = $book->archivo_pdf;
        $book->delete();

        $this->eliminarArchivoPublicoSiLocal($portada);
        $this->eliminarArchivoPublicoSiLocal($pdf);

        return redirect()
            ->route('books.index')
            ->with('success', 'Obra eliminada del catálogo.');
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

    public function externalSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:160'],
        ]);
        $query = trim($validated['q']);
        $results = [];
        $sources = [
            'open_library' => 'unavailable',
            'gutenberg' => 'unavailable',
        ];

        try {
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(6)
                ->get('https://openlibrary.org/search.json', [
                    'q' => $query,
                    'limit' => 10,
                ]);

            if ($response->successful()) {
                $sources['open_library'] = 'ok';
                $documents = $response->json('docs', []);

                if (is_array($documents)) {
                    foreach ($documents as $document) {
                        if (! is_array($document) || empty($document['title'])) {
                            continue;
                        }

                        $archiveId = data_get($document, 'ia.0');
                        $workKey = $document['key'] ?? null;
                        $readUrl = is_string($archiveId)
                            ? "https://archive.org/details/{$archiveId}"
                            : (is_string($workKey) ? "https://openlibrary.org{$workKey}" : null);

                        $results[] = [
                            'title' => $document['title'],
                            'authors' => $document['author_name'] ?? [],
                            'year' => $document['first_publish_year'] ?? null,
                            'cover_url' => ! empty($document['cover_i'])
                                ? 'https://covers.openlibrary.org/b/id/'.$document['cover_i'].'-M.jpg'
                                : null,
                            'source' => 'Open Library',
                            'read_url' => $readUrl,
                        ];
                    }
                }
            }
        } catch (ConnectionException) {
            $sources['open_library'] = 'unavailable';
        }

        try {
            $response = Http::acceptJson()
                ->connectTimeout(3)
                ->timeout(6)
                ->get('https://gutendex.com/books/', [
                    'search' => $query,
                ]);

            if ($response->successful()) {
                $sources['gutenberg'] = 'ok';
                $books = $response->json('results', []);

                if (is_array($books)) {
                    foreach (array_slice($books, 0, 10) as $book) {
                        if (! is_array($book) || empty($book['title'])) {
                            continue;
                        }

                        $bookId = $book['id'] ?? null;
                        $formats = $book['formats'] ?? [];
                        $readUrl = is_array($formats)
                            ? (($formats['text/html'] ?? null)
                                ?? $formats['application/epub+zip']
                                ?? (is_numeric($bookId) ? "https://www.gutenberg.org/ebooks/{$bookId}" : null))
                            : null;

                        $results[] = [
                            'title' => $book['title'],
                            'authors' => collect($book['authors'] ?? [])->pluck('name')->all(),
                            'year' => null,
                            'cover_url' => is_array($formats) ? ($formats['image/jpeg'] ?? null) : null,
                            'source' => 'Project Gutenberg',
                            'read_url' => $readUrl,
                        ];
                    }
                }
            }
        } catch (ConnectionException) {
            $sources['gutenberg'] = 'unavailable';
        }

        if (! in_array('ok', $sources, true)) {
            return response()->json([
                'message' => 'No se pudo consultar Open Library ni Project Gutenberg en este momento.',
                'sources' => $sources,
            ], 502);
        }

        return response()->json([
            'data' => $results,
            'meta' => [
                'query' => $query,
                'sources' => $sources,
            ],
        ]);
    }

    private function prepararDatosLibro(
        BookRequest $request,
        ?Book $book = null,
        ?BookLinkInterconnectorService $interconnector = null
    ): array {
        $datos = collect($request->validated())->except(['portada_file', 'pdf_file'])->toArray();

        if ($request->hasFile('portada_file')) {
            $path = $request->file('portada_file')->store('covers', 'public');
            $datos['portada_url'] = '/storage/'.ltrim($path, '/');
            if ($book) {
                $this->eliminarArchivoPublicoSiLocal($book->portada_url);
            }
        } elseif ($book && empty($datos['portada_url'])) {
            $datos['portada_url'] = $book->portada_url;
        }

        if ($request->hasFile('pdf_file')) {
            $path = $request->file('pdf_file')->store('pdfs', 'public');
            $datos['archivo_pdf'] = '/storage/'.ltrim($path, '/');
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
