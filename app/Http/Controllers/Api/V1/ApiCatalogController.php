<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiCatalogController extends Controller
{
    public function categories(): JsonResponse
    {
        $categories = Category::withCount('books')->orderBy('id')->get();

        return response()->json(['data' => $categories]);
    }

    public function books(Request $request): JsonResponse
    {
        $filtros = $request->only(['buscar', 'category_id', 'formato', 'disponibilidad', 'orden']);

        $books = Book::with('category')
            ->withAvg(['reviews' => fn ($q) => $q->where('visible', true)], 'rating')
            ->withCount(['reviews' => fn ($q) => $q->where('visible', true)])
            ->filtro($filtros)
            ->ordenar($filtros['orden'] ?? null)
            ->paginate(min(50, max(1, (int) $request->query('per_page', 12))));

        return response()->json($books);
    }

    public function show(Book $book): JsonResponse
    {
        $book->load([
            'category',
            'reviews' => fn ($q) => $q->where('visible', true)->with('user:id,name')->latest(),
        ])
            ->loadAvg(['reviews' => fn ($q) => $q->where('visible', true)], 'rating')
            ->loadCount(['reviews' => fn ($q) => $q->where('visible', true)]);

        return response()->json(['data' => $book]);
    }
}
