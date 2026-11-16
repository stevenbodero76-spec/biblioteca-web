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
            $destino = ($user && $user->esAdmin()) ? 'dashboard' : 'home';

            return redirect()
                ->route($destino)
                ->with('error', 'Acceso restringido: tu rol (' . ($user->rol ?? 'invitado') . ') no tiene permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
