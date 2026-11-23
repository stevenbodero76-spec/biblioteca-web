<?php

namespace App\Http\Controllers;

use App\Models\KarmaMovement;
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
            'codigo_operacion' => ['nullable', 'required_if:metodo_pago,yape_plin', 'string', 'min:4', 'max:60'],
        ], [
            'codigo_operacion.required_if' => 'Ingresa el código de operación Yape/Plin para validar tu suscripción.',
        ]);

        $reader = $request->user()->reader;
        if (! $reader) {
            return back()->with('error', 'Solo las cuentas con ficha de lector pueden gestionar planes de suscripción.');
        }

        $plan = $datos['plan_suscripcion'];
        if ($reader->plan_suscripcion === $plan) {
            return back()->with('error', "Ya tienes activo el {$reader->etiquetaPlan()}.");
        }

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

        if ($plan === 'free') {
            return back()->with('success', 'Has cambiado a Lector Free (pago individual por reserva o compra).');
        }

        $yaRecibioBonoReciente = KarmaMovement::where('reader_id', $reader->id)
            ->where('referencia_tipo', 'plan')
            ->where('created_at', '>=', now()->subDays(30))
            ->exists();

        $bonusTexto = '';
        if (! $yaRecibioBonoReciente) {
            $reader->registrarKarma(20, "Activación {$reader->etiquetaPlan()}", 'plan', $reader->id);
            $bonusTexto = ' y ganaste +20 Puntos Nova';
        }

        return back()->with('success', "🎉 ¡Plan actualizado a {$reader->etiquetaPlan()}! Ya puedes reservar alquileres en S/ 0.00{$bonusTexto}.");
    }
}
