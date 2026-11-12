<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = [
        'category_id',
        'isbn',
        'titulo',
        'autor',
        'editorial',
        'anio_publicacion',
        'formato',
        'sinopsis',
        'portada_url',
        'archivo_pdf',
        'ubicacion_estante',
        'stock_total',
        'stock_disponible',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function estaDisponible(): bool
    {
        return $this->stock_disponible > 0;
    }

    public function scopeFiltro(Builder $query, array $filtros): Builder
    {
        return $query
            ->when($filtros['buscar'] ?? null, function (Builder $q, string $buscar) {
                $q->where(function (Builder $sub) use ($buscar) {
                    $sub->where('titulo', 'like', "%{$buscar}%")
                        ->orWhere('autor', 'like', "%{$buscar}%")
                        ->orWhere('isbn', 'like', "%{$buscar}%")
                        ->orWhere('editorial', 'like', "%{$buscar}%");
                });
            })
            ->when($filtros['category_id'] ?? null, fn (Builder $q, $catId) => $q->where('category_id', $catId))
            ->when($filtros['formato'] ?? null, fn (Builder $q, $formato) => $q->where('formato', $formato))
            ->when($filtros['disponibilidad'] ?? null, function (Builder $q, string $disp) {
                if ($disp === 'disponible') {
                    $q->where('stock_disponible', '>', 0);
                } elseif ($disp === 'agotado') {
                    $q->where('stock_disponible', '=', 0);
                }
            });
    }
}

