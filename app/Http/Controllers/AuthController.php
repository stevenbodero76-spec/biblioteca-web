<?php

namespace App\Http\Controllers;

use App\Models\KarmaMovement;
use App\Models\Reader;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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
            $user = Auth::user();

            if ($user && $user->estaSuspendido()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()
                    ->withErrors(['email' => 'Tu cuenta se encuentra suspendida por administración. Contáctanos para regularizar tu estado.'])
                    ->onlyInput('email');
            }

            $request->session()->regenerate();
            $destino = $user->esAdmin() ? route('dashboard') : route('home');

            return redirect()
                ->intended($destino)
                ->with('success', '¡Bienvenido(a) a NOVAETHER™, ' . $user->name . '!');
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
            $nuevoUsuario = User::create([
                'name' => trim($datos['nombres'] . ' ' . $datos['apellidos']),
                'email' => $datos['email'],
                'rol' => 'cliente',
                'password' => Hash::make($datos['password']),
            ]);

            $reader = Reader::create([
                'user_id' => $nuevoUsuario->id,
                'dni_codigo' => $datos['dni_codigo'],
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'email' => $datos['email'],
                'telefono' => $datos['telefono'] ?? null,
                'tipo' => 'publico',
                'plan_suscripcion' => 'free',
                'estado' => 'activo',
                'puntos_karma' => 50,
            ]);

            KarmaMovement::create([
                'reader_id' => $reader->id,
                'puntos' => 50,
                'motivo' => 'Bono de bienvenida NOVAETHER™',
                'referencia_tipo' => 'register',
                'referencia_id' => $nuevoUsuario->id,
            ]);

            return $nuevoUsuario;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('home')
            ->with('success', '¡Cuenta creada con éxito! Ya eres miembro de NOVAETHER™ y recibiste 50 Puntos Nova de bienvenida.');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Has cerrado sesión correctamente.');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $user = User::where('email', $request->input('email'))->first();

        if ($user) {
            $token = Password::createToken($user);
            $user->sendPasswordResetNotification($token);
        }

        // Respuesta uniforme para evitar enumeración de correos registrados
        return back()->with('success', 'Si el correo pertenece a una cuenta activa, enviamos las instrucciones de recuperación.');
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        $status = Password::reset(
            $datos,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Tu contraseña ha sido restablecida correctamente. Ya puedes iniciar sesión.');
        }

        return back()
            ->withErrors(['email' => 'El enlace de recuperación es inválido o ha expirado.'])
            ->withInput($request->only('email'));
    }
}
