<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->rol, $roles, true)) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'Acceso restringido: tu rol (' . ($user->rol ?? 'invitado') . ') no tiene permiso para realizar esta acción.');
        }

        return $next($request);
    }
}

