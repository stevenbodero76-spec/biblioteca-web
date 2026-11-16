<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(Request $request): View
    {
        $reader = $request->user()?->reader;

        return view('plans', compact('reader'));
    }

    public function subscribe(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'plan_suscripcion' => ['required', 'in:free,estudiante,pro'],
            'metodo_pago' => ['nullable', 'in:yape_plin,tarjeta'],
            'codigo_operacion' => ['nullable', 'string', 'max:60'],
        ]);

        $reader = $request->user()->reader;
        if (! $reader) {
            return back()->with('error', 'Solo las cuentas con ficha de lector pueden gestionar planes de suscripción.');
        }

        $plan = $datos['plan_suscripcion'];
        $tipoLegacy = match ($plan) {
            'pro' => 'docente',
            'estudiante' => 'estudiante',
            default => 'publico',
        };

        $reader->update([
            'plan_suscripcion' => $plan,
            'tipo' => $tipoLegacy,
            'plan_vence_at' => $plan === 'free' ? null : now()->addMonth()->toDateString(),
        ]);

        if ($plan !== 'free') {
            $reader->registrarKarma(20, "Activación {$reader->etiquetaPlan()}", 'plan', $reader->id);
        }

        $mensaje = $plan === 'free'
            ? 'Has cambiado a Lector Free (pago individual por reserva o compra).'
            : "🎉 ¡Plan actualizado a {$reader->etiquetaPlan()}! Ya puedes reservar alquileres en S/ 0.00 y ganaste +20 Puntos Nova.";

        return back()->with('success', $mensaje);
    }
}
