<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReaderRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->load('reader');
        $reader = $user->reader;

        $movimientosKarma = $reader
            ? $reader->karmaMovements()->take(15)->get()
            : collect();

        $resumenLector = [
            'prestamos_activos' => $reader?->prestamosActivosCount() ?? 0,
            'favoritos' => $user->favorites()->count(),
        ];

        return view('profile.edit', compact('user', 'reader', 'movimientosKarma', 'resumenLector'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $reader = $user->reader;

        $identity = ReaderRequest::identityRules(false);

        $datos = $request->validate([
            'nombres' => $identity['nombres'],
            'apellidos' => $identity['apellidos'],
            'telefono' => $identity['telefono'],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user->id),
                Rule::unique('readers', 'email')->ignore($reader?->id),
            ],
        ], ReaderRequest::identityMessages());

        DB::transaction(function () use ($user, $reader, $datos) {
            $user->update([
                'name' => trim($datos['nombres'].' '.$datos['apellidos']),
                'email' => $datos['email'],
            ]);

            if ($reader) {
                $reader->update([
                    'nombres' => $datos['nombres'],
                    'apellidos' => $datos['apellidos'],
                    'telefono' => $datos['telefono'] ?? null,
                    'email' => $datos['email'],
                ]);
            }
        });

        return back()->with('success', 'Tus datos de perfil fueron actualizados correctamente.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'current_password.current_password' => 'La contraseña actual ingresada es incorrecta.',
        ]);

        $request->user()->update([
            'password' => Hash::make($datos['password']),
        ]);

        return back()->with('success', 'Contraseña actualizada con éxito.');
    }
}
