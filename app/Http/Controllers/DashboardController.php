<?php

namespace App\Http\Controllers;

use App\Actions\SyncOverdueLoansAction;
use App\Models\Book;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\Loan;
use App\Models\Reader;
use App\Models\Review;
use App\Models\Waitlist;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(SyncOverdueLoansAction $syncOverdueLoans): View
    {
        // Sincroniza vencimientos de préstamos activos
        $syncOverdueLoans->execute();

        $resumen = [
            'total_titulos' => Book::count(),
            'total_ejemplares' => Book::sum('stock_total'),
            'ejemplares_disponibles' => Book::sum('stock_disponible'),
            'libros_agotados' => Book::where('stock_disponible', 0)->count(),
            'lectores_activos' => Reader::where('estado', 'activo')->count(),
            'prestamos_activos' => Loan::where('estado', 'prestado')->where('estado_pago', '!=', 'anulado')->count(),
            'prestamos_vencidos' => Loan::where('estado', 'vencido')->where('estado_pago', '!=', 'anulado')->count(),
            'devoluciones_completadas' => Loan::where('estado', 'devuelto')->where('estado_pago', '!=', 'anulado')->count(),
            'en_lista_espera' => Waitlist::whereIn('estado', ['en_espera', 'notificado'])->count(),
            'reclamos_abiertos' => Complaint::where('estado', '!=', 'resuelto')->count(),
            'resenas_totales' => Review::count(),
        ];

        $categoriasResumen = Category::withCount('books')
            ->withSum('books', 'stock_disponible')
            ->orderByDesc('books_count')
            ->get();

        $ultimosPrestamos = Loan::with(['book.category', 'reader', 'user'])
            ->latest()
            ->take(8)
            ->get();

        $librosSinStock = Book::with('category')
            ->withCount(['waitlists as en_espera_count' => fn ($q) => $q->whereIn('estado', ['en_espera', 'notificado'])])
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
