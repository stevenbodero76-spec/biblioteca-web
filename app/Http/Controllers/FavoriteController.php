<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Favorite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $books = $request->user()
            ->favoriteBooks()
            ->with('category')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('favorites.created_at')
            ->paginate(12);

        $favoriteBookIds = $books->pluck('id')->all();

        return view('favorites.index', compact('books', 'favoriteBookIds'));
    }

    public function toggle(Request $request, Book $book): RedirectResponse|JsonResponse
    {
        $userId = $request->user()->id;

        $existente = Favorite::where('user_id', $userId)
            ->where('book_id', $book->id)
            ->first();

        if ($existente) {
            $existente->delete();
            $favorito = false;
            $mensaje = "«{$book->titulo}» fue retirado de tu estantería.";
        } else {
            Favorite::create([
                'user_id' => $userId,
                'book_id' => $book->id,
            ]);
            $favorito = true;
            $mensaje = "❤️ «{$book->titulo}» guardado en Mi Estantería.";
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'favorito' => $favorito,
                'book_id' => $book->id,
                'message' => $mensaje,
            ]);
        }

        return back()->with('success', $mensaje);
    }
}
