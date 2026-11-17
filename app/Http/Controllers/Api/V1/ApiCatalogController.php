<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use App\Services\BookLinkInterconnectorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiCatalogController extends Controller
{
    public function categories(): JsonResponse
    {
        $categories = Category::withCount('books')->orderBy('id')->get();

        return response()->json(['data' => $categories]);
    }

    public function books(Request $request, BookLinkInterconnectorService $interconnector): JsonResponse
    {
        $filtros = $request->only(['buscar', 'category_id', 'formato', 'disponibilidad', 'orden']);

        $books = Book::with('category')
            ->withAvg(['reviews' => fn ($q) => $q->where('visible', true)], 'rating')
            ->withCount(['reviews' => fn ($q) => $q->where('visible', true)])
            ->filtro($filtros)
            ->ordenar($filtros['orden'] ?? null)
            ->paginate(min(50, max(1, (int) $request->query('per_page', 12))));

        $books->getCollection()->transform(function (Book $book) use ($interconnector) {
            $enlaces = $interconnector->resolverEnlacesLectura($book);
            $book->setAttribute('enlace_lectura_directa', $enlaces['principal_url']);

            return $book;
        });

        return response()->json($books);
    }

    public function show(Book $book, BookLinkInterconnectorService $interconnector): JsonResponse
    {
        $book->load([
            'category',
            'reviews' => fn ($q) => $q->where('visible', true)->with('user:id,name')->latest(),
        ])
            ->loadAvg(['reviews' => fn ($q) => $q->where('visible', true)], 'rating')
            ->loadCount(['reviews' => fn ($q) => $q->where('visible', true)]);

        $enlaces = $interconnector->resolverEnlacesLectura($book);
        if (empty($book->archivo_pdf)) {
            $book->setAttribute('archivo_pdf', $enlaces['principal_url']);
        }

        return response()->json([
            'data' => $book,
            'enlaces_lectura' => $enlaces,
            'interconectados' => $interconnector->obtenerLibrosInterconectados($book, 4),
        ]);
    }
}
