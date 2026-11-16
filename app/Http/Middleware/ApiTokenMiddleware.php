<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearer = $request->bearerToken();

        if (! $bearer || ! str_contains($bearer, '|')) {
            return response()->json(['message' => 'Token Bearer requerido o con formato inválido.'], 401);
        }

        [$id, $plain] = explode('|', $bearer, 2);

        /** @var ApiToken|null $token */
        $token = ApiToken::with('user.reader')->find($id);

        if (! $token || ! hash_equals($token->token, hash('sha256', $plain)) || $token->estaExpirado()) {
            return response()->json(['message' => 'Token inválido o expirado.'], 401);
        }

        $user = $token->user;
        if (! $user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 401);
        }

        if ($user->estaSuspendido()) {
            return response()->json(['message' => 'Cuenta de cliente suspendida.'], 403);
        }

        $token->forceFill(['last_used_at' => now()])->save();
        Auth::setUser($user);
        $request->setUserResolver(fn () => $user);

        return $next($request);
    }
}
