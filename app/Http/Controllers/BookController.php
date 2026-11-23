<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookRequest;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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

    public function show(Book $book): View
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

        $relacionados = Book::with('category')
            ->where('category_id', $book->category_id)
            ->where('id', '!=', $book->id)
            ->take(4)
            ->get();

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

        $terminoBusqueda = rawurlencode(trim($book->titulo.' '.$book->autor));
        $tituloBusqueda = rawurlencode($book->titulo);
        $enlacesLectura = [
            'fuentes' => [
                [
                    'nombre' => 'Open Library',
                    'url' => "https://openlibrary.org/search?q={$terminoBusqueda}",
                ],
                [
                    'nombre' => 'Internet Archive',
                    'url' => "https://archive.org/search?query={$terminoBusqueda}",
                ],
                [
                    'nombre' => 'Project Gutenberg',
                    'url' => "https://www.gutenberg.org/ebooks/search/?query={$tituloBusqueda}",
                ],
            ],
        ];

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
            'waitlistCount',
            'proximaDevolucion',
            'enlacesLectura',
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

    public function store(BookRequest $request): RedirectResponse
    {
        $datos = $this->prepararDatosLibro($request);
        $datos['precio_alquiler'] = $datos['precio_alquiler'] ?? 8.00;
        $datos['precio_venta'] = $datos['precio_venta'] ?? 49.90;

        Book::create($datos);

        return redirect()
            ->route('books.index')
            ->with('success', 'Obra registrada exitosamente en el catálogo.');
    }

    public function edit(Book $book): View
    {
        $categories = Category::orderBy('nombre')->get();
        $prestamosActivos = $book->prestamosActivosCount();

        return view('books.edit', compact('book', 'categories', 'prestamosActivos'));
    }

    public function update(BookRequest $request, Book $book): RedirectResponse
    {
        $datos = $this->prepararDatosLibro($request, $book);
        $datos['precio_alquiler'] = $datos['precio_alquiler'] ?? $book->precio_alquiler;
        $datos['precio_venta'] = $datos['precio_venta'] ?? $book->precio_venta;

        $book->update($datos);

        return redirect()
            ->route('books.index')
            ->with('success', 'Datos de la obra actualizados correctamente.');
    }

    public function destroy(Book $book): RedirectResponse
    {
        if ($book->loans()->exists() || $book->orderItems()->exists()) {
            return back()->with('error', 'No se puede eliminar esta obra porque tiene historial de préstamos o ventas asociadas.');
        }

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', 'Obra eliminada del catálogo.');
    }

    public function buscarPorIsbn(string $isbn): JsonResponse
    {
        $cleanIsbn = preg_replace('/[^0-9Xx]/', '', $isbn);

        if (strlen($cleanIsbn) < 10) {
            return response()->json([
                'encontrado' => false,
                'mensaje' => 'Ingresa un ISBN válido (10 o 13 dígitos).',
            ], 422);
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

            return response()->json([
                'encontrado' => true,
                'titulo' => $payload['title'] ?? '',
                'autor' => $autores,
                'editorial' => $editoriales,
                'anio_publicacion' => $anio,
                'portada_url' => $portada,
                'sinopsis' => $payload['notes'] ?? ($payload['subtitle'] ?? ''),
            ]);
        } catch (\Throwable) {
            return response()->json([
                'encontrado' => false,
                'mensaje' => 'Error de conexión al consultar Open Library.',
            ], 503);
        }
    }

    private function prepararDatosLibro(BookRequest $request, ?Book $book = null): array
    {
        $datos = collect($request->validated())->except(['portada_file', 'pdf_file'])->toArray();

        if ($request->hasFile('portada_file')) {
            $path = $request->file('portada_file')->store('covers', 'public');
            $datos['portada_url'] = '/storage/'.ltrim($path, '/');
        } elseif ($book && empty($datos['portada_url'])) {
            $datos['portada_url'] = $book->portada_url;
        }

        if ($request->hasFile('pdf_file')) {
            $path = $request->file('pdf_file')->store('pdfs', 'public');
            $datos['archivo_pdf'] = '/storage/'.ltrim($path, '/');
        } elseif ($book && empty($datos['archivo_pdf'])) {
            $datos['archivo_pdf'] = $book->archivo_pdf;
        }

        return $datos;
    }
}
