<?php

namespace App\Http\Controllers;

use App\Models\KarmaMovement;
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
            ? KarmaMovement::where('reader_id', $reader->id)->latest('id')->take(15)->get()
            : collect();

        $ordenesRecientes = $user->orders()->latest()->take(5)->get();

        return view('profile.edit', compact('user', 'reader', 'movimientosKarma', 'ordenesRecientes'));
    }

    public function update(Request $request): RedirectResponse
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
