<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Loan;
use App\Models\Reader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->only(['buscar', 'estado']);

        $loans = Loan::with(['book.category', 'reader', 'user'])
            ->filtro($filtros)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('loans.index', compact('loans', 'filtros'));
    }

    public function create(Request $request): View
    {
        $books = Book::with('category')->orderBy('titulo')->get();
        $readers = Reader::orderBy('apellidos')->get();
        $selectedBookId = $request->query('book_id');
        $miPerfilReader = Auth::user()?->reader;

        return view('loans.create', compact('books', 'readers', 'selectedBookId', 'miPerfilReader'));
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'book_id' => ['required', 'exists:books,id'],
            'reader_id' => ['required', 'exists:readers,id'],
            'fecha_prestamo' => ['required', 'date'],
            'fecha_devolucion_esperada' => ['required', 'date', 'after_or_equal:fecha_prestamo'],
            'observaciones' => ['nullable', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($datos) {
            /** @var Book $book */
            $book = Book::lockForUpdate()->findOrFail($datos['book_id']);

            /** @var Reader $reader */
            $reader = Reader::findOrFail($datos['reader_id']);

            // PRUEBA CLAVE DEL DOCENTE: Impedir un préstamo sin disponibilidad
            if ($book->stock_disponible <= 0) {
                throw ValidationException::withMessages([
                    'book_id' => "PRÉSTAMO RECHAZADO: El libro «{$book->titulo}» NO tiene ejemplares disponibles actualmente (Disponibilidad: 0 de {$book->stock_total}).",
                ]);
            }

            // Regla adicional de negocio: Impedir préstamo a lector suspendido
            if ($reader->estado !== 'activo') {
                throw ValidationException::withMessages([
                    'reader_id' => "PRÉSTAMO RECHAZADO: El lector «{$reader->nombre_completo}» se encuentra SUSPENDIDO.",
                ]);
            }

            Loan::create([
                'book_id' => $book->id,
                'reader_id' => $reader->id,
                'user_id' => Auth::id(),
                'fecha_prestamo' => $datos['fecha_prestamo'],
                'fecha_devolucion_esperada' => $datos['fecha_devolucion_esperada'],
                'estado' => 'prestado',
                'observaciones' => $datos['observaciones'] ?? null,
            ]);

            // Descontar 1 unidad de la disponibilidad del libro
            $book->decrement('stock_disponible');

            return redirect()
                ->route('loans.index')
                ->with('success', "Préstamo registrado exitosamente. Disponibilidad de «{$book->titulo}» actualizada a {$book->fresh()->stock_disponible} ejemplar(es).");
        });
    }

    public function devolver(Loan $loan): RedirectResponse
    {
        if ($loan->estado === 'devuelto') {
            return back()->with('error', 'Este préstamo ya fue registrado como devuelto anteriormente.');
        }

        DB::transaction(function () use ($loan) {
            $loan->update([
                'estado' => 'devuelto',
                'fecha_devolucion_real' => now()->toDateString(),
            ]);

            $book = Book::lockForUpdate()->findOrFail($loan->book_id);
            if ($book->stock_disponible < $book->stock_total) {
                $book->increment('stock_disponible');
            }
        });

        return redirect()
            ->route('loans.index')
            ->with('success', "Devolución registrada. Se repuso +1 al stock disponible de «{$loan->book->titulo}».");
    }

    public function destroy(Loan $loan): RedirectResponse
    {
        DB::transaction(function () use ($loan) {
            if ($loan->estado !== 'devuelto') {
                $book = Book::lockForUpdate()->find($loan->book_id);
                if ($book && $book->stock_disponible < $book->stock_total) {
                    $book->increment('stock_disponible');
                }
            }

            $loan->delete();
        });

        return redirect()
            ->route('loans.index')
            ->with('success', 'Registro de préstamo eliminado.');
    }
}

