<?php

namespace App\Http\Controllers;

use App\Actions\CreateLoanAction;
use App\Actions\ReturnLoanAction;
use App\Http\Requests\StoreLoanRequest;
use App\Models\Book;
use App\Models\Loan;
use App\Models\Reader;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Loan::class);

        $user = $request->user();
        $filtros = $request->only(['buscar', 'estado', 'estado_pago']);

        $loans = Loan::with(['book.category', 'reader', 'user'])
            ->visiblePara($user)
            ->filtro($filtros)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $miPerfilReader = $user->reader;

        $misWaitlists = ($user->esCliente() && $miPerfilReader)
            ? $miPerfilReader->waitlists()
                ->with('book.category')
                ->whereIn('estado', ['en_espera', 'notificado'])
                ->latest()
                ->get()
            : collect();

        return view('loans.index', compact('loans', 'filtros', 'misWaitlists', 'miPerfilReader'));
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Loan::class);

        $user = $request->user();
        $books = Book::with('category')->orderBy('titulo')->limit(200)->get();
        $selectedBookId = $request->query('book_id');

        $readers = $user->esAdmin()
            ? Reader::where('estado', 'activo')->orderBy('apellidos')->limit(200)->get()
            : collect();
        $miPerfilReader = $user->reader;

        return view('loans.create', compact('books', 'readers', 'selectedBookId', 'miPerfilReader'));
    }

    public function store(StoreLoanRequest $request, CreateLoanAction $createLoanAction): RedirectResponse
    {
        Gate::authorize('create', Loan::class);

        $loan = $createLoanAction->execute($request->validated(), $request->user());

        return redirect()
            ->route('loans.ticket', $loan)
            ->with('success', "Préstamo público gratuito #{$loan->id} confirmado con éxito. Aquí tienes tu comprobante QR.");
    }

    public function ticket(Loan $loan): View
    {
        Gate::authorize('view', $loan);

        $loan->load(['book.category', 'reader', 'user']);

        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => true,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
        ]);

        $payload = json_encode([
            'ticket' => $loan->codigoTicket(),
            'libro' => $loan->book->titulo,
            'isbn' => $loan->book->isbn,
            'lector' => $loan->reader->nombre_completo,
            'dni' => $loan->reader->dni_codigo,
            'modalidad' => $loan->etiquetaMetodoPago(),
            'vence' => $loan->fecha_devolucion_esperada->format('Y-m-d'),
        ], JSON_UNESCAPED_UNICODE);

        $qrDataUri = (new QRCode($options))->render($payload);

        return view('loans.ticket', compact('loan', 'qrDataUri'));
    }

    public function devolver(Loan $loan, ReturnLoanAction $returnLoanAction): RedirectResponse
    {
        Gate::authorize('returnBook', $loan);

        if (! $returnLoanAction->execute($loan)) {
            return back()->with('error', 'Este préstamo ya fue cerrado o devuelto anteriormente.');
        }

        $mensajeKarma = $returnLoanAction->lastKarmaAwarded > 0
            ? " (+{$returnLoanAction->lastKarmaAwarded} Puntos Nova otorgados al lector por devolución puntual)"
            : '';

        return redirect()
            ->route('loans.index')
            ->with('success', "Devolución registrada. Stock de «{$loan->book->titulo}» actualizado{$mensajeKarma}.");
    }

    public function destroy(Loan $loan): RedirectResponse
    {
        Gate::authorize('delete', $loan);

        DB::transaction(function () use ($loan) {
            if ($loan->estado !== 'devuelto' && ! $loan->estaAnulado()) {
                $loan->book?->restaurarStockYNotificar(1, false);
            }

            $loan->delete();
        });

        return redirect()
            ->route('loans.index')
            ->with('success', 'Registro de préstamo eliminado.');
    }
}
