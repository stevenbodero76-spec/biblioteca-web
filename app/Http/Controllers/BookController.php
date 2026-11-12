<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->only(['buscar', 'category_id', 'formato', 'disponibilidad']);

        $books = Book::with('category')
            ->filtro($filtros)
            ->orderByDesc('id')
            ->paginate(12)
            ->withQueryString();

        $categories = Category::withCount('books')->orderBy('id')->get();

        // Estadísticas públicas del escaparate virtual
        $stats = [
            'total_titulos' => Book::count(),
            'total_ejemplares' => Book::sum('stock_total'),
            'disponibles_ahora' => Book::sum('stock_disponible'),
            'categorias_activas' => Category::count(),
        ];

        // Destacados de Cómics y Manga para la vitrina principal
        $comicsDestacados = Book::with('category')
            ->whereHas('category', fn ($q) => $q->where('nombre', 'like', '%Cómics%'))
            ->take(5)
            ->get();

        return view('books.index', compact('books', 'categories', 'filtros', 'stats', 'comicsDestacados'));
    }

    public function show(Book $book): View
    {
        $book->load('category');

        $relacionados = Book::with('category')
            ->where('category_id', $book->category_id)
            ->where('id', '!=', $book->id)
            ->take(4)
            ->get();

        return view('books.show', compact('book', 'relacionados'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('nombre')->get();

        return view('books.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'isbn' => ['required', 'string', 'max:30', 'unique:books,isbn'],
            'titulo' => ['required', 'string', 'max:200'],
            'autor' => ['required', 'string', 'max:150'],
            'editorial' => ['nullable', 'string', 'max:100'],
            'anio_publicacion' => ['nullable', 'integer', 'min:1000', 'max:' . (date('Y') + 1)],
            'formato' => ['required', 'in:fisico,digital,ambos'],
            'sinopsis' => ['nullable', 'string'],
            'portada_url' => ['nullable', 'url', 'max:255'],
            'archivo_pdf' => ['nullable', 'url', 'max:255'],
            'ubicacion_estante' => ['nullable', 'string', 'max:50'],
            'stock_total' => ['required', 'integer', 'min:1', 'max:500'],
            'stock_disponible' => ['required', 'integer', 'min:0', 'lte:stock_total'],
        ]);

        Book::create($datos);

        return redirect()
            ->route('books.index')
            ->with('success', 'Libro registrado exitosamente en el catálogo.');
    }

    public function edit(Book $book): View
    {
        $categories = Category::orderBy('nombre')->get();

        return view('books.edit', compact('book', 'categories'));
    }

    public function update(Request $request, Book $book): RedirectResponse
    {
        $datos = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'isbn' => ['required', 'string', 'max:30', 'unique:books,isbn,' . $book->id],
            'titulo' => ['required', 'string', 'max:200'],
            'autor' => ['required', 'string', 'max:150'],
            'editorial' => ['nullable', 'string', 'max:100'],
            'anio_publicacion' => ['nullable', 'integer', 'min:1000', 'max:' . (date('Y') + 1)],
            'formato' => ['required', 'in:fisico,digital,ambos'],
            'sinopsis' => ['nullable', 'string'],
            'portada_url' => ['nullable', 'url', 'max:255'],
            'archivo_pdf' => ['nullable', 'url', 'max:255'],
            'ubicacion_estante' => ['nullable', 'string', 'max:50'],
            'stock_total' => ['required', 'integer', 'min:1', 'max:500'],
            'stock_disponible' => ['required', 'integer', 'min:0', 'lte:stock_total'],
        ]);

        $book->update($datos);

        return redirect()
            ->route('books.index')
            ->with('success', 'Datos del libro actualizados correctamente.');
    }

    public function destroy(Book $book): RedirectResponse
    {
        if ($book->loans()->exists()) {
            return back()->with('error', 'No se puede eliminar este libro porque tiene historial de préstamos asociados (Integridad Referencial).');
        }

        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', 'Libro eliminado del catálogo.');
    }
}
