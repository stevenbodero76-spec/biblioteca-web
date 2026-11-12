<?php

namespace App\Http\Controllers;

use App\Models\Reader;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credenciales, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $destino = Auth::user()->esStaff() ? route('dashboard') : route('home');

            return redirect()
                ->intended($destino)
                ->with('success', '¡Bienvenido(a) a NOVAETHER™, ' . Auth::user()->name . '!');
        }

        return back()
            ->withErrors(['email' => 'Las credenciales ingresadas no coinciden con nuestros registros.'])
            ->onlyInput('email');
    }

    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }

        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'dni_codigo' => ['required', 'string', 'max:20', 'unique:readers,dni_codigo'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email', 'unique:readers,email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($datos) {
            // 1. Crear cuenta de acceso en tabla 'users'
            $nuevoUsuario = User::create([
                'name' => trim($datos['nombres'] . ' ' . $datos['apellidos']),
                'email' => $datos['email'],
                'rol' => 'cliente',
                'password' => Hash::make($datos['password']),
            ]);

            // 2. Crear ficha de cliente/lector en tabla 'readers' para préstamos inmediatos
            Reader::create([
                'dni_codigo' => $datos['dni_codigo'],
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'] ?? null,
                'tipo' => 'publico',
                'estado' => 'activo',
            ]);

            return $nuevoUsuario;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('home')
            ->with('success', '🎉 ¡Cuenta creada con éxito! Ya eres miembro de NOVAETHER™ y puedes reservar o leer cualquier obra.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Has cerrado sesión correctamente.');
    }
}
