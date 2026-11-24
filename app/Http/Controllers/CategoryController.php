<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::withCount('books')
            ->withSum('books', 'stock_disponible')
            ->orderBy('id')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:categories,nombre'],
            'icono' => ['nullable', 'string', 'max:30'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        Category::create([
            'nombre' => trim($datos['nombre']),
            'icono' => filled($datos['icono'] ?? null) ? trim((string) $datos['icono']) : '📚',
            'descripcion' => $datos['descripcion'] ?? null,
        ]);

        Book::limpiarCacheCatalogo();

        return back()->with('success', 'Categoría creada y sincronizada con el catálogo.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100', Rule::unique('categories', 'nombre')->ignore($category->id)],
            'icono' => ['nullable', 'string', 'max:30'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $category->update([
            'nombre' => trim($datos['nombre']),
            'icono' => filled($datos['icono'] ?? null) ? trim((string) $datos['icono']) : '📚',
            'descripcion' => $datos['descripcion'] ?? null,
        ]);

        Book::limpiarCacheCatalogo();

        return back()->with('success', "Categoría «{$category->nombre}» actualizada correctamente.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->books()->exists()) {
            return back()->with('error', 'No se puede eliminar esta categoría porque tiene obras asociadas en el catálogo.');
        }

        $category->delete();
        Book::limpiarCacheCatalogo();

        return back()->with('success', 'Categoría eliminada correctamente.');
    }
}
