<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CreateLoanAction;
use App\Actions\SubmitBookReviewAction;
use App\Http\Controllers\Controller;
use App\Models\Book;
use App\Models\Favorite;
use App\Models\Loan;
use App\Models\Waitlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApiCustomerController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('reader');
        $reader = $user->reader;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'rol' => $user->rol,
            ],
            'reader' => $reader ? [
                'id' => $reader->id,
                'dni_codigo' => $reader->dni_codigo,
                'telefono' => $reader->telefono,
                'etiqueta_plan' => $reader->etiquetaPlan(),
                'puntos_karma' => $reader->puntos_karma,
                'nivel_karma' => $reader->nivelKarma(),
                'estado' => $reader->estado,
            ] : null,
            'resumen' => [
                'prestamos_activos' => $reader?->prestamosActivosCount() ?? 0,
                'favoritos' => $user->favorites()->count(),
            ],
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $reader = $user->reader;

        $datos = $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user->id),
                Rule::unique('readers', 'email')->ignore($reader?->id),
            ],
        ]);

        DB::transaction(function () use ($user, $reader, $datos) {
            $user->update([
                'name' => trim($datos['nombres'] . ' ' . $datos['apellidos']),
                'email' => $datos['email'],
            ]);

            $reader?->update([
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'telefono' => $datos['telefono'] ?? null,
                'email' => $datos['email'],
            ]);
        });

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'user' => $user->fresh('reader'),
        ]);
    }

    public function favorites(Request $request): JsonResponse
    {
        $books = $request->user()->favoriteBooks()->with('category')->get();

        return response()->json(['data' => $books]);
    }

    public function toggleFavorite(Request $request, Book $book): JsonResponse
    {
        $userId = $request->user()->id;
        $existente = Favorite::where('user_id', $userId)->where('book_id', $book->id)->first();

        if ($existente) {
            $existente->delete();
            $favorito = false;
        } else {
            Favorite::create(['user_id' => $userId, 'book_id' => $book->id]);
            $favorito = true;
        }

        return response()->json(['favorito' => $favorito, 'book_id' => $book->id]);
    }

    public function joinWaitlist(Request $request, Book $book): JsonResponse
    {
        $reader = $request->user()->reader;
        if (! $reader || $reader->estado !== 'activo') {
            return response()->json(['message' => 'Se requiere una ficha de lector activa.'], 422);
        }

        if ($book->estaDisponible()) {
            return response()->json(['message' => 'La obra ya tiene stock disponible; puedes reservarla directamente.'], 422);
        }

        $existente = Waitlist::where('book_id', $book->id)
            ->where('reader_id', $reader->id)
            ->whereIn('estado', ['en_espera', 'notificado'])
            ->first();

        if ($existente) {
            return response()->json(['message' => 'Ya te encuentras anotado en la lista de espera.', 'data' => $existente], 422);
        }

        $waitlist = Waitlist::create([
            'book_id' => $book->id,
            'reader_id' => $reader->id,
            'estado' => 'en_espera',
        ]);

        return response()->json(['message' => 'Agregado a la lista de espera.', 'data' => $waitlist], 201);
    }

    public function cancelWaitlist(Request $request, Waitlist $waitlist): JsonResponse
    {
        $user = $request->user();
        if (! $user->esAdmin() && (int) $waitlist->reader_id !== (int) $user->reader?->id) {
            return response()->json(['message' => 'No autorizado para cancelar este turno.'], 403);
        }

        $waitlist->update(['estado' => 'cancelado']);

        return response()->json(['message' => 'Turno de lista de espera cancelado.']);
    }

    public function storeReview(Request $request, Book $book, SubmitBookReviewAction $action): JsonResponse
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

        return response()->json([
            'message' => 'Reseña publicada correctamente.',
            'karma_awarded' => $resultado['karma_awarded'],
            'data' => $resultado['review'],
        ], 201);
    }

    public function loans(Request $request): JsonResponse
    {
        $loans = Loan::with('book.category')
            ->visiblePara($request->user())
            ->latest()
            ->paginate(15);

        $loans->getCollection()->transform(function (Loan $loan) {
            $loan->setAttribute('codigo_ticket', $loan->codigoTicket());

            return $loan;
        });

        return response()->json($loans);
    }

    public function storeLoan(Request $request, CreateLoanAction $action): JsonResponse
    {
        $datos = $request->validate([
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'duracion_dias' => ['nullable', 'integer', 'in:7,14,30'],
            'metodo_pago' => ['nullable', 'in:gratuito,membresia,yape_plin,mostrador'],
            'codigo_operacion' => ['nullable', 'string', 'max:60'],
        ]);

        $user = $request->user();
        if (! $user->reader) {
            return response()->json(['message' => 'Tu cuenta no posee ficha de lector activa.'], 422);
        }

        $duracion = (int) ($datos['duracion_dias'] ?? 7);

        $loan = $action->execute([
            'book_id' => $datos['book_id'],
            'reader_id' => $user->reader->id,
            'fecha_prestamo' => now()->toDateString(),
            'fecha_devolucion_esperada' => now()->addDays($duracion)->toDateString(),
            'metodo_pago' => $datos['metodo_pago'] ?? 'gratuito',
            'codigo_operacion' => $datos['codigo_operacion'] ?? null,
        ], $user);

        return response()->json([
            'message' => 'Préstamo gratuito generado correctamente.',
            'codigo_ticket' => $loan->codigoTicket(),
            'data' => $loan->load('book'),
        ], 201);
    }
}
