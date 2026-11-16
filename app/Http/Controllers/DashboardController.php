<?php

namespace App\Http\Controllers;

use App\Actions\SyncOverdueLoansAction;
use App\Models\Book;
use App\Models\Category;
use App\Models\Complaint;
use App\Models\Loan;
use App\Models\Order;
use App\Models\Reader;
use App\Models\Review;
use App\Models\Waitlist;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(SyncOverdueLoansAction $syncOverdueLoans): View
    {
        // Sincroniza vencimientos y libera stock de reservas/órdenes impagas expiradas
        $syncOverdueLoans->execute();

        $ingresosLoansMes = (float) Loan::where('estado_pago', 'pagado')
            ->whereDoesntHave('orderItem')
            ->whereMonth('fecha_pago', now()->month)
            ->whereYear('fecha_pago', now()->year)
            ->sum('monto');

        $ingresosOrdersMes = (float) Order::where('estado_pago', 'pagado')
            ->whereMonth('fecha_pago', now()->month)
            ->whereYear('fecha_pago', now()->year)
            ->sum('total');

        $ingresosHoyLoans = (float) Loan::where('estado_pago', 'pagado')
            ->whereDoesntHave('orderItem')
            ->whereDate('fecha_pago', today())
            ->sum('monto');
        $ingresosHoyOrders = (float) Order::where('estado_pago', 'pagado')
            ->whereDate('fecha_pago', today())
            ->sum('total');

        $montoPendienteLoans = (float) Loan::where('estado_pago', 'pendiente')
            ->whereDoesntHave('orderItem')
            ->sum('monto');
        $montoPendienteOrders = (float) Order::where('estado_pago', 'pendiente')->sum('total');

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
            'ingresos_mes' => round($ingresosLoansMes + $ingresosOrdersMes, 2),
            'ingresos_hoy' => round($ingresosHoyLoans + $ingresosHoyOrders, 2),
            'monto_por_validar' => round($montoPendienteLoans + $montoPendienteOrders, 2),
            'pagos_pendientes_count' => Loan::where('estado_pago', 'pendiente')->count() + Order::where('estado_pago', 'pendiente')->count(),
            'reclamos_abiertos' => Complaint::where('estado', '!=', 'resuelto')->count(),
            'resenas_totales' => Review::count(),
            'ordenes_mes' => Order::where('estado_pago', '!=', 'anulado')->whereMonth('created_at', now()->month)->count(),
        ];

        $categoriasResumen = Category::withCount('books')
            ->withSum('books', 'stock_disponible')
            ->orderByDesc('books_count')
            ->get();

        $ultimosPrestamos = Loan::with(['book.category', 'reader', 'user'])
            ->latest()
            ->take(8)
            ->get();

        $pagosPendientesLoans = Loan::with(['book', 'reader'])
            ->where('estado_pago', 'pendiente')
            ->latest()
            ->take(5)
            ->get();

        $pagosPendientesOrders = Order::with(['user', 'reader'])
            ->where('estado_pago', 'pendiente')
            ->latest()
            ->take(5)
            ->get();

        $librosSinStock = Book::with('category')
            ->withCount(['waitlists as en_espera_count' => fn ($q) => $q->whereIn('estado', ['en_espera', 'notificado'])])
            ->where('stock_disponible', 0)
            ->get();

        return view('dashboard', compact(
            'resumen',
            'categoriasResumen',
            'ultimosPrestamos',
            'pagosPendientesLoans',
            'pagosPendientesOrders',
            'librosSinStock'
        ));
    }
}
