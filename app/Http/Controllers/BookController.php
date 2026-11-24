<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookRequest;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
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
    public function index(Request $request, BookLinkInterconnectorService$interconnector): View|JsonResponse
    {
        $filtros =$request->only(['buscar', 'category_id', 'formato', 'idioma', 'licencia', 'disponibilidad', 'orden']);

        $books = Book::with('category')
            ->withAvg(['reviews' => fn ($q) =>$q->where('visible', true)], 'rating')
            ->withCount(['reviews' => fn ($q) =>$q->where('visible', true)])
            ->filtro($filtros)
            ->ordenar($filtros['orden'] ?? null)
            ->paginate(12)
            ->withQueryString();

        $books->getCollection()->each(function (Book$book) use ($interconnector) {$book->setAttribute('fuentes_lectura', $interconnector->resolverEnlacesLectura($book)['fuentes']);
        });

        /** @var User|null $user */$user = Auth::user();
        $favoriteBookIds =$user?->favorites()->pluck('book_id')->all() ?? [];

        $librosExternos = [];
        $terminoBusqueda = trim((string) ($filtros['buscar'] ?? ''));
        if ($terminoBusqueda !== '' && $books->total() === 0) {$librosExternos = $interconnector->buscarEnRedesExternas($terminoBusqueda, 6);
        }

        if ($request->ajax() || $request->boolean('partial')) {$html = view('books.partials.grid', compact('books', 'favoriteBookIds', 'filtros', 'librosExternos'))->render();

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
            'valorados' => Book::withAvg(['reviews' => fn ($q) =>$q->where('visible', true)], 'rating')
                ->withCount(['reviews' => fn ($q) =>$q->where('visible', true)])
                ->orderByDesc('reviews_avg_rating')
                ->orderByDesc('reviews_count')
                ->take(5)
                ->get()
                ->toArray(),
            'manga' => Book::withAvg(['reviews' => fn ($q) =>$q->where('visible', true)], 'rating')
                ->withCount(['reviews' => fn ($q) =>$q->where('visible', true)])
                ->whereHas('category', fn ($q) =>$q->where('nombre', 'like', '%Cómics%'))
                ->take(5)
                ->get()
                ->toArray(),
            'tech' => Book::withAvg(['reviews' => fn ($q) =>$q->where('visible', true)], 'rating')
                ->withCount(['reviews' => fn ($q) =>$q->where('visible', true)])
                ->whereHas('category', fn ($q) =>$q->where('nombre', 'like', '%Tecnología%')->orWhere('nombre', 'like', '%Negocios%')->orWhere('nombre', 'like', '%Desarrollo%'))
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

    public function show(Book $book, BookLinkInterconnectorService$interconnector): View
    {
        /** @var User|null $user */$user = Auth::user();
        $esAdmin =$user?->esAdmin() ?? false;

        $book->load([
            'category',
            'reviews' => function ($q) use ($esAdmin) {
                if (! $esAdmin) {$q->where('visible', true);
                }
                $q->with('user')->latest();
            },
        ])
            ->loadAvg(['reviews' => fn ($q) =>$q->where('visible', true)], 'rating')
            ->loadCount(['reviews' => fn ($q) =>$q->where('visible', true)]);

        $enlacesLectura = $interconnector->resolverEnlacesLectura($book);
        if (! empty($enlacesLectura['embed_url'])) {
            $book->setAttribute('archivo_pdf',$enlacesLectura['embed_url']);
        } elseif (empty($book->archivo_pdf)) {
            $book->setAttribute('archivo_pdf',$enlacesLectura['principal_url']);
        }

        $relacionados = $interconnector->obtenerLibrosInterconectados($book, 4);

        $waitlistCount =$book->waitlists()
            ->whereIn('estado', ['en_espera', 'notificado'])
            ->count();

        $proximaDevolucion = !$book->estaDisponible()
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

        if ($user) {
            $esFavorito =$user->favorites()->where('book_id', $book->id)->exists();$miResena = $book->reviews()->where('user_id',$user->id)->first();

            if ($user->reader) {
                $miWaitlist =$book->waitlists()
                    ->where('reader_id', $user->reader->id)
                    ->whereIn('estado', ['en_espera', 'notificado'])
                    ->first();

                if ($miWaitlist) {
                    $miPosicionCola =$book->waitlists()
                        ->where