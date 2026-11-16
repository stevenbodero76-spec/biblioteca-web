<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Waitlist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WaitlistController extends Controller
{
    public function adminIndex(): View
    {
        $topDemanda = Book::with('category')
            ->withCount(['waitlists as esperando_count' => fn ($q) => $q->whereIn('estado', ['en_espera', 'notificado'])])
            ->whereHas('waitlists', fn ($q) => $q->whereIn('estado', ['en_espera', 'notificado']))
            ->orderByDesc('esperando_count')
            ->take(6)
            ->get();

        $waitlists = Waitlist::with(['book.category', 'reader'])
            ->whereIn('estado', ['en_espera', 'notificado'])
            ->orderBy('book_id')
            ->orderBy('id')
            ->paginate(15);

        return view('admin.waitlists.index', compact('topDemanda', 'waitlists'));
    }

    public function store(Book $book): RedirectResponse
    {
        $reader = Auth::user()?->reader;

        if (! $reader) {
            return back()->with('error', 'Solo las cuentas con ficha de lector activa pueden unirse a la lista de espera.');
        }

        if ($reader->estado !== 'activo') {
            return back()->with('error', 'Tu cuenta de lector está suspendida; no puedes unirte a listas de espera.');
        }

        if ($book->estaDisponible()) {
            return redirect()
                ->route('loans.create', ['book_id' => $book->id])
                ->with('success', "«{$book->titulo}» ya tiene ejemplares disponibles. ¡Puedes reservarlo ahora mismo!");
        }

        $yaExiste = Waitlist::where('book_id', $book->id)
            ->where('reader_id', $reader->id)
            ->whereIn('estado', ['en_espera', 'notificado'])
            ->exists();

        if ($yaExiste) {
            return back()->with('error', 'Ya te encuentras anotado en la lista de espera para esta obra.');
        }

        Waitlist::create([
            'book_id' => $book->id,
            'reader_id' => $reader->id,
            'estado' => 'en_espera',
        ]);

        return back()->with('success', "🔔 Te agregamos a la lista de espera de «{$book->titulo}». Te avisaremos en cuanto se devuelva un ejemplar.");
    }

    public function destroy(Waitlist $waitlist): RedirectResponse
    {
        $user = Auth::user();

        if (! $user || (! $user->esAdmin() && (int) $waitlist->reader_id !== (int) $user->reader?->id)) {
            abort(403, 'No tienes permiso para cancelar este turno de espera.');
        }

        $waitlist->update(['estado' => 'cancelado']);

        return back()->with('success', 'Turno de lista de espera cancelado.');
    }
}
