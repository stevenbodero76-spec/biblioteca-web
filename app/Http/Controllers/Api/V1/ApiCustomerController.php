<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CheckoutCartAction;
use App\Actions\CreateLoanAction;
use App\Actions\SubmitBookReviewAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutOrderRequest;
use App\Models\Book;
use App\Models\Favorite;
use App\Models\Loan;
use App\Models\Order;
use App\Models\Waitlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
                'plan_suscripcion' => $reader->plan_suscripcion,
                'etiqueta_plan' => $reader->etiquetaPlan(),
                'plan_vence_at' => $reader->plan_vence_at?->toDateString(),
                'puntos_karma' => $reader->puntos_karma,
                'nivel_karma' => $reader->nivelKarma(),
                'estado' => $reader->estado,
            ] : null,
            'resumen' => [
                'prestamos_activos' => $reader?->prestamosActivosCount() ?? 0,
                'favoritos' => $user->favorites()->count(),
                'ordenes' => $user->orders()->count(),
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
            return response()->json(['message' => 'Se requiere una ficha de cliente activa.'], 422);
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
            'duracion_dias' => ['required', 'integer', 'in:7,14,30'],
            'metodo_pago' => ['required', 'in:membresia,yape_plin,mostrador'],
            'codigo_operacion' => ['nullable', 'string', 'max:60'],
        ]);

        $user = $request->user();
        if (! $user->reader) {
            return response()->json(['message' => 'Tu cuenta no posee ficha de cliente activa.'], 422);
        }

        $loan = $action->execute([
            'book_id' => $datos['book_id'],
            'reader_id' => $user->reader->id,
            'fecha_prestamo' => now()->toDateString(),
            'fecha_devolucion_esperada' => now()->addDays((int) $datos['duracion_dias'])->toDateString(),
            'metodo_pago' => $datos['metodo_pago'],
            'codigo_operacion' => $datos['codigo_operacion'] ?? null,
        ], $user);

        return response()->json([
            'message' => 'Reserva generada correctamente.',
            'codigo_ticket' => $loan->codigoTicket(),
            'data' => $loan->load('book'),
        ], 201);
    }

    public function orders(Request $request): JsonResponse
    {
        $orders = Order::with('items.book')
            ->visiblePara($request->user())
            ->latest()
            ->paginate(15);

        return response()->json($orders);
    }

    public function showOrder(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('view', $order);

        return response()->json([
            'data' => $order->load('items.book', 'items.loan'),
        ]);
    }

    public function storeOrder(CheckoutOrderRequest $request, CheckoutCartAction $action): JsonResponse
    {
        $datos = $request->validated();

        $bookIds = collect($datos['items'])->pluck('book_id')->unique()->all();
        $books = Book::whereIn('id', $bookIds)->get()->keyBy('id');

        $cartItems = collect($datos['items'])->map(function (array $entry) use ($books) {
            $book = $books->get((int) $entry['book_id']);
            $tipo = $entry['tipo_operacion'];
            $qty = $tipo === 'venta' ? max(1, (int) ($entry['cantidad'] ?? 1)) : 1;

            return [
                'book_id' => $book->id,
                'book' => $book,
                'tipo_operacion' => $tipo,
                'cantidad' => $qty,
                'duracion_dias' => $tipo === 'alquiler' ? (int) ($entry['duracion_dias'] ?? 7) : null,
            ];
        });

        $order = $action->execute($cartItems, $request->user(), $datos);

        return response()->json([
            'message' => 'Orden registrada correctamente.',
            'data' => $order->load('items.book', 'items.loan'),
        ], 201);
    }
}
