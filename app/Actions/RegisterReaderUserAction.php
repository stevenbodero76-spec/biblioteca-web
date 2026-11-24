<?php

namespace App\Actions;

use App\Models\KarmaMovement;
use App\Models\Reader;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterReaderUserAction
{
    /**
     * Registra en una sola transacción la cuenta de usuario, ficha de lector y bono inicial de Puntos Nova.
     *
     * @param  array<string, mixed>  $datos
     */
    public function execute(
        array $datos,
        string $tipo = 'publico',
        string $estado = 'activo',
        string $motivoKarma = 'Bono de bienvenida NOVAETHER™',
        string $referenciaTipo = 'register'
    ): User {
        return DB::transaction(function () use ($datos, $tipo, $estado, $motivoKarma, $referenciaTipo) {
            $user = User::create([
                'name' => trim($datos['nombres'].' '.$datos['apellidos']),
                'email' => $datos['email'],
                'rol' => 'cliente',
                'password' => Hash::make($datos['password']),
            ]);

            $reader = Reader::create([
                'user_id' => $user->id,
                'dni_codigo' => filled($datos['dni_codigo'] ?? null) ? trim((string) $datos['dni_codigo']) : null,
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'] ?? null,
                'tipo' => $tipo,
                'estado' => $estado,
                'puntos_karma' => 50,
            ]);

            KarmaMovement::create([
                'reader_id' => $reader->id,
                'puntos' => 50,
                'motivo' => $motivoKarma,
                'referencia_tipo' => $referenciaTipo,
                'referencia_id' => $user->id,
            ]);

            return $user->load('reader');
        });
    }
}
