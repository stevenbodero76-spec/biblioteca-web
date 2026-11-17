<?php

namespace App\Http\Controllers;

use App\Actions\SubmitBookReviewAction;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function adminIndex(Request $request): View
    {
        $estado = $request->query('estado');

        $reviews = Review::with(['book', 'user', 'moderadoPor'])
            ->when($estado === 'ocultas', fn ($q) => $q->where('visible', false))
            ->when($estado === 'spoilers', fn ($q) => $q->where('es_spoiler', true))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews', 'estado'));
    }

    public function store(Request $request, Book $book, SubmitBookReviewAction $action): RedirectResponse
    {
        $datos = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comentario' => ['required', 'string', 'min:5', 'max:1000'],
            'es_spoiler' => ['nullable', 'boolean'],
        ]);

        $resultado = $action->execute(
            $book,
            $request->user(),
            (int) $datos['rating'],
            $datos['comentario'],
            $request->boolean('es_spoiler')
        );

        $bonusMsg = $resultado['karma_awarded'] > 0
            ? " (+{$resultado['karma_awarded']} Puntos Nova por tu primera reseña de esta obra)"
            : '';

        return back()->with('success', '⭐ ¡Gracias! Tu reseña fue publicada en la comunidad' . $bonusMsg . '.');
    }

    public function toggleVisibility(Request $request, Review $review): RedirectResponse
    {
        $nuevoEstado = ! $review->visible;
        $review->update([
            'visible' => $nuevoEstado,
            'moderado_por_id' => $request->user()->id,
        ]);

        return back()->with('success', $nuevoEstado ? 'Reseña visible nuevamente al público.' : 'Reseña ocultada por moderación administrativa.');
    }

    public function destroy(Request $request, Review $review): RedirectResponse
    {
        $user = $request->user();
        if (! $user->esAdmin() && (int) $review->user_id !== (int) $user->id) {
            abort(403, 'No tienes permiso para eliminar esta reseña.');
        }

        $review->delete();

        return back()->with('success', 'Reseña eliminada correctamente.');
    }
}
