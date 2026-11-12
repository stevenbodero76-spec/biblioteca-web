<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reader extends Model
{
    protected $fillable = [
        'dni_codigo',
        'nombres',
        'apellidos',
        'email',
        'telefono',
        'tipo',
        'estado',
    ];

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    public function scopeFiltro(Builder $query, array $filtros): Builder
    {
        return $query
            ->when($filtros['buscar'] ?? null, function (Builder $q, string $buscar) {
                $q->where(function (Builder $sub) use ($buscar) {
                    $sub->where('nombres', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('dni_codigo', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%");
                });
            })
            ->when($filtros['tipo'] ?? null, fn (Builder $q, $tipo) => $q->where('tipo', $tipo))
            ->when($filtros['estado'] ?? null, fn (Builder $q, $estado) => $q->where('estado', $estado));
    }
}

