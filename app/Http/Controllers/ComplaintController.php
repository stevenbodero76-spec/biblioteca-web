<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    public function create(): View
    {
        return view('legal.reclamaciones');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre_completo' => ['required', 'string', 'max:150'],
            'dni_ce' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:150'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'tipo' => ['required', 'in:reclamo,queja'],
            'detalle' => ['required', 'string', 'min:10', 'max:2000'],
            'pedido_consumidor' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $codigo = 'LRC-' . date('Y') . '-' . strtoupper(Str::random(6));

        Complaint::create([
            ...$datos,
            'codigo_reclamo' => $codigo,
            'estado' => 'registrado',
        ]);

        return back()->with('success', "Hoja de Reclamación registrada con código oficial {$codigo}. Se atenderá dentro del plazo legal.");
    }

    public function adminIndex(Request $request): View
    {
        $filtros = $request->only(['estado', 'tipo', 'buscar']);

        $complaints = Complaint::with('respondidoPor')
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            ->when($filtros['tipo'] ?? null, fn ($q, $tipo) => $q->where('tipo', $tipo))
            ->when(trim((string) ($filtros['buscar'] ?? '')), function ($q, $buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('codigo_reclamo', 'like', "%{$buscar}%")
                        ->orWhere('nombre_completo', 'like', "%{$buscar}%")
                        ->orWhere('dni_ce', 'like', "%{$buscar}%");
                });
            })
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.complaints.index', compact('complaints', 'filtros'));
    }

    public function adminUpdate(Request $request, Complaint $complaint): RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['required', 'in:registrado,en_revision,resuelto'],
            'respuesta_admin' => ['nullable', 'string', 'max:2000'],
        ]);

        $complaint->update([
            'estado' => $datos['estado'],
            'respuesta_admin' => $datos['respuesta_admin'] ?? $complaint->respuesta_admin,
            'respondido_por_id' => $request->user()->id,
            'respondido_at' => now(),
        ]);

        return back()->with('success', "Hoja {$complaint->codigo_reclamo} actualizada correctamente.");
    }
}
