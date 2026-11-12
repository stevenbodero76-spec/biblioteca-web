<?php

namespace App\Http\Controllers;

use App\Models\Reader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReaderController extends Controller
{
    public function index(Request $request): View
    {
        $filtros = $request->only(['buscar', 'tipo', 'estado']);

        $readers = Reader::withCount([
            'loans',
            'loans as prestamos_activos_count' => fn ($q) => $q->whereIn('estado', ['prestado', 'vencido']),
        ])
            ->filtro($filtros)
            ->orderBy('apellidos')
            ->paginate(10)
            ->withQueryString();

        return view('readers.index', compact('readers', 'filtros'));
    }

    public function create(): View
    {
        return view('readers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'dni_codigo' => ['required', 'string', 'max:20', 'unique:readers,dni_codigo'],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:readers,email'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'tipo' => ['required', 'in:estudiante,docente,publico'],
            'estado' => ['required', 'in:activo,suspendido'],
        ]);

        Reader::create($datos);

        return redirect()
            ->route('readers.index')
            ->with('success', 'Lector registrado exitosamente.');
    }

    public function edit(Reader $reader): View
    {
        return view('readers.edit', compact('reader'));
    }

    public function update(Request $request, Reader $reader): RedirectResponse
    {
        $datos = $request->validate([
            'dni_codigo' => ['required', 'string', 'max:20', 'unique:readers,dni_codigo,' . $reader->id],
            'nombres' => ['required', 'string', 'max:100'],
            'apellidos' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:readers,email,' . $reader->id],
            'telefono' => ['nullable', 'string', 'max:20'],
            'tipo' => ['required', 'in:estudiante,docente,publico'],
            'estado' => ['required', 'in:activo,suspendido'],
        ]);

        $reader->update($datos);

        return redirect()
            ->route('readers.index')
            ->with('success', 'Datos del lector actualizados correctamente.');
    }

    public function destroy(Reader $reader): RedirectResponse
    {
        if ($reader->loans()->exists()) {
            return back()->with('error', 'No se puede eliminar este lector porque tiene historial de préstamos en la base de datos.');
        }

        $reader->delete();

        return redirect()
            ->route('readers.index')
            ->with('success', 'Lector eliminado correctamente.');
    }
}

