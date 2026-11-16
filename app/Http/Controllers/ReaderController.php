<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReaderRequest;
use App\Models\KarmaMovement;
use App\Models\Reader;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ReaderController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->only(['buscar', 'tipo', 'plan_suscripcion', 'estado']);

        $readers = Reader::with('user')
            ->withCount([
                'loans',
                'loans as prestamos_activos_count' => fn ($q) => $q->whereIn('estado', ['prestado', 'vencido'])->where('estado_pago', '!=', 'anulado'),
                'orders',
            ])
            ->filtro($filtros)
            ->orderBy('apellidos')
            ->paginate(10)
            ->withQueryString();

        return view('readers.index', compact('readers', 'filtros'));
    }

    public function create(): View
    {
        return view('readers.create');
    }

    public function store(ReaderRequest $request): RedirectResponse
    {
        $datos = $request->validated();
        $clavePlana = trim((string) ($datos['password'] ?? '')) ?: Str::random(10);
        $plan = $datos['plan_suscripcion'] ?? match ($datos['tipo']) {
            'docente' => 'pro',
            'estudiante' => 'estudiante',
            default => 'free',
        };

        DB::transaction(function () use ($datos, $clavePlana, $plan) {
            $user = User::create([
                'name' => trim($datos['nombres'] . ' ' . $datos['apellidos']),
                'email' => $datos['email'],
                'rol' => 'cliente',
                'password' => Hash::make($clavePlana),
            ]);

            $reader = Reader::create([
                'user_id' => $user->id,
                'dni_codigo' => $datos['dni_codigo'],
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'] ?? null,
                'tipo' => $datos['tipo'],
                'plan_suscripcion' => $plan,
                'plan_vence_at' => $datos['plan_vence_at'] ?? ($plan !== 'free' ? now()->addMonth()->toDateString() : null),
                'estado' => $datos['estado'],
                'puntos_karma' => 50,
            ]);

            KarmaMovement::create([
                'reader_id' => $reader->id,
                'puntos' => 50,
                'motivo' => 'Registro inicial de cliente por administración',
                'referencia_tipo' => 'admin_create',
                'referencia_id' => $user->id,
            ]);
        });

        $infoClave = empty($datos['password'])
            ? " Acceso web creado con clave temporal: {$clavePlana}"
            : ' Acceso web activado con la contraseña indicada.';

        return redirect()
            ->route('readers.index')
            ->with('success', 'Cliente / lector registrado y sincronizado con cuenta de usuario.' . $infoClave);
    }

    public function edit(Reader $reader): View
    {
        $reader->load('user');

        return view('readers.edit', compact('reader'));
    }

    public function update(ReaderRequest $request, Reader $reader): RedirectResponse
    {
        $datos = $request->validated();

        DB::transaction(function () use ($datos, $reader) {
            $plan = $datos['plan_suscripcion'] ?? $reader->plan_suscripcion;
            $nombreCompleto = trim($datos['nombres'] . ' ' . $datos['apellidos']);

            if ($reader->user) {
                $userUpdates = [
                    'name' => $nombreCompleto,
                    'email' => $datos['email'],
                ];
                if (! empty($datos['password'])) {
                    $userUpdates['password'] = Hash::make($datos['password']);
                }
                $reader->user->update($userUpdates);
                $userId = $reader->user->id;
            } else {
                $nuevoUser = User::create([
                    'name' => $nombreCompleto,
                    'email' => $datos['email'],
                    'rol' => 'cliente',
                    'password' => Hash::make(! empty($datos['password']) ? $datos['password'] : Str::random(10)),
                ]);
                $userId = $nuevoUser->id;
            }

            $reader->update([
                'user_id' => $userId,
                'dni_codigo' => $datos['dni_codigo'],
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'] ?? null,
                'tipo' => $datos['tipo'],
                'plan_suscripcion' => $plan,
                'plan_vence_at' => $datos['plan_vence_at'] ?? ($plan !== 'free' && ! $reader->plan_vence_at ? now()->addMonth()->toDateString() : $reader->plan_vence_at),
                'estado' => $datos['estado'],
            ]);
        });

        return redirect()
            ->route('readers.index')
            ->with('success', 'Datos del cliente y cuenta de acceso sincronizados correctamente.');
    }

    public function destroy(Reader $reader): RedirectResponse
    {
        if ($reader->loans()->exists() || $reader->orders()->exists()) {
            return back()->with('error', 'No se puede eliminar este cliente porque tiene historial de préstamos u órdenes en la base de datos.');
        }

        DB::transaction(function () use ($reader) {
            $user = $reader->user;
            $reader->delete();
            if ($user && $user->esCliente() && ! $user->orders()->exists() && ! $user->loans()->exists()) {
                $user->delete();
            }
        });

        return redirect()
            ->route('readers.index')
            ->with('success', 'Cliente eliminado correctamente.');
    }
}
