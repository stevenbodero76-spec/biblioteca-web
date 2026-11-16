<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->estaSuspendido()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Tu cuenta se encuentra suspendida. Contacta con soporte o registra un reclamo.',
                ], 403);
            }

            return redirect()
                ->route('login')
                ->with('error', 'Tu cuenta se encuentra suspendida por administración. Contáctanos para regularizar tu estado.');
        }

        return $next($request);
    }
}
