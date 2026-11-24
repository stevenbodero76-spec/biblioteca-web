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

        // Consolidación de agregaciones para minimizar consultas al motor SQL
        $bookMetrics = Book::selectRaw(
            'COUNT(*) as total_titulos,
            COALESCE(SUM(stock_total), 0) as total_ejemplares,
            COALESCE(SUM(stock_disponible), 0) as ejemplares_disponibles,
            COALESCE(SUM(CASE WHEN stock_disponible = 0 THEN 1 ELSE 0 END), 0) as libros_agotados'
        )->first();

        $loanCounts = Loan::selectRaw('estado, COUNT(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $resumen = [
            'total_titulos' => (int) ($bookMetrics?->total_titulos ?? 0),
            'total_ejemplares' => (int) ($bookMetrics?->total_ejemplares ?? 0),
            'ejemplares_disponibles' => (int) ($bookMetrics?->ejemplares_disponibles ?? 0),
            'libros_agotados' => (int) ($bookMetrics?->libros_agotados ?? 0),
            'lectores_activos' => Reader::where('estado', 'activo')->count(),
            'prestamos_activos' => (int) ($loanCounts['prestado'] ?? 0),
            'prestamos_vencidos' => (int) ($loanCounts['vencido'] ?? 0),
            'devoluciones_completadas' => (int) ($loanCounts['devuelto'] ?? 0),
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
