<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\KarmaMovement;
use App\Models\Reader;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ApiAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        /** @var User|null $user */
        $user = User::with('reader')->where('email', $datos['email'])->first();

        if (! $user || ! Hash::check($datos['password'], $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas.'], 401);
        }

        if ($user->estaSuspendido()) {
            return response()->json(['message' => 'Tu cuenta se encuentra suspendida por administración.'], 403);
        }

        $token = $user->createApiToken($datos['device_name'] ?? 'mobile-app');

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'rol' => $user->rol,
                'reader' => $user->reader ? [
                    'id' => $user->reader->id,
                    'dni_codigo' => $user->reader->dni_codigo,
                    'etiqueta_plan' => $user->reader->etiquetaPlan(),
                    'puntos_karma' => $user->reader->puntos_karma,
                    'estado' => $user->reader->estado,
                ] : null,
            ],
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'dni_codigo' => ['nullable', 'string', 'max:20', 'unique:readers,dni_codigo'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email', 'unique:readers,email'],
            'password' => ['required', 'string', 'min:6'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = DB::transaction(function () use ($datos) {
            $nuevo = User::create([
                'name' => trim($datos['nombres'] . ' ' . $datos['apellidos']),
                'email' => $datos['email'],
                'rol' => 'cliente',
                'password' => Hash::make($datos['password']),
            ]);

            $reader = Reader::create([
                'user_id' => $nuevo->id,
                'dni_codigo' => $datos['dni_codigo'] ?? null,
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'] ?? null,
                'tipo' => 'publico',
                'estado' => 'activo',
                'puntos_karma' => 50,
            ]);

            KarmaMovement::create([
                'reader_id' => $reader->id,
                'puntos' => 50,
                'motivo' => 'Bono de bienvenida NOVAETHER™ Móvil',
                'referencia_tipo' => 'api_register',
                'referencia_id' => $nuevo->id,
            ]);

            return $nuevo->load('reader');
        });

        return response()->json([
            'token' => $user->createApiToken($datos['device_name'] ?? 'mobile-app'),
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'rol' => $user->rol,
            ],
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        $bearer = $request->bearerToken();
        if ($bearer && str_contains($bearer, '|')) {
            [$id] = explode('|', $bearer, 2);
            $request->user()->apiTokens()->where('id', $id)->delete();
        }

        return response()->json(['message' => 'Sesión móvil cerrada correctamente.']);
    }
}
