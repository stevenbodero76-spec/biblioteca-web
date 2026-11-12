<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\Reader;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $resumen = [
            'total_titulos' => Book::count(),
            'total_ejemplares' => Book::sum('stock_total'),
            'ejemplares_disponibles' => Book::sum('stock_disponible'),
            'libros_agotados' => Book::where('stock_disponible', 0)->count(),
            'lectores_activos' => Reader::where('estado', 'activo')->count(),
            'prestamos_activos' => Loan::where('estado', 'prestado')->count(),
            'prestamos_vencidos' => Loan::where('estado', 'vencido')
                ->orWhere(fn ($q) => $q->where('estado', 'prestado')->whereDate('fecha_devolucion_esperada', '<', now()))
                ->count(),
            'devoluciones_completadas' => Loan::where('estado', 'devuelto')->count(),
        ];

        $categoriasResumen = Category::withCount('books')
            ->withSum('books', 'stock_disponible')
            ->orderByDesc('books_count')
            ->get();

        $ultimosPrestamos = Loan::with(['book.category', 'reader', 'user'])
            ->latest()
            ->take(6)
            ->get();

        $librosSinStock = Book::with('category')
            ->where('stock_disponible', 0)
            ->get();

        return view('dashboard', compact(
            'resumen',
            'categoriasResumen',
            'ultimosPrestamos',
            'librosSinStock'
        ));
    }
}

