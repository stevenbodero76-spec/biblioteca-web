<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Book extends Model
{
    /**
     * Claves de caché del catálogo y menú compartidas para invalidación centralizada.
     */
    public const CATALOG_CACHE_KEYS = [
        'catalog_categories_with_count_v2',
        'catalog_home_stats',
        'catalog_showcase_tabs_v2',
        'nav_categories_v2',
    ];

    protected $fillable = [
        'category_id',
        'isbn',
        'titulo',
        'autor',
        'editorial',
        'anio_publicacion',
        'idioma',
        'formato',
        'licencia',
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

    public function waitlists(): HasMany
    {
        return $this->hasMany(Waitlist::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function estaDisponible(): bool
    {
        return $this->stock_disponible > 0;
    }

    public function etiquetaIdioma(): string
    {
        return match ($this->idioma) {
            'es' => 'Español',
            'en' => 'Inglés',
            default => 'Sin especificar',
        };
    }

    public function etiquetaLicencia(): string
    {
        return match ($this->licencia) {
            'dominio_publico' => 'Dominio Público',
            'creative_commons' => 'Creative Commons',
            'acceso_abierto' => 'Acceso Abierto',
            default => 'Sin especificar',
        };
    }

    /**
     * Invalida toda la caché de estadísticas, categorías y vitrina del catálogo.
     */
    public static function limpiarCacheCatalogo(): void
    {
        foreach (self::CATALOG_CACHE_KEYS as $key) {
            Cache::forget($key);
        }
    }

    public function prestamosActivosCount(): int
    {
        return $this->loans()
            ->whereIn('estado', ['prestado', 'vencido'])
            ->count();
    }

    /**
     * Notifica al primer lector en cola de espera si la obra cuenta con stock disponible.
     */
    public function notificarSiguienteEnEspera(): bool
    {
        if ((int) $this->stock_disponible <= 0) {
            return false;
        }

        $siguienteEnEspera = Waitlist::where('book_id', $this->id)
            ->where('estado', 'en_espera')
            ->orderBy('id')
            ->first();

        if (! $siguienteEnEspera) {
            return false;
        }

        return $siguienteEnEspera->update([
            'estado' => 'notificado',
            'notificado_at' => now(),
        ]);
    }

    /**
     * Repone ejemplares al catálogo bajo bloqueo de fila y notifica a la cola de espera.
     */
    public function restaurarStockYNotificar(int $cantidad = 1, bool $incluirStockTotal = false): void
    {
        /** @var self $locked */
        $locked = self::lockForUpdate()->findOrFail($this->id);

        if ($incluirStockTotal) {
            $locked->increment('stock_total', $cantidad);
            $locked->increment('stock_disponible', $cantidad);
        } else {
            $incremento = min($cantidad, max(0, (int) $locked->stock_total - (int) $locked->stock_disponible));
            if ($incremento > 0) {
                $locked->increment('stock_disponible', $incremento);
            }
        }

        $locked->fresh()?->notificarSiguienteEnEspera();
    }

    public function esPdfEmbeddable(): bool
    {
        if (! $this->archivo_pdf) {
            return false;
        }

        return str_starts_with($this->archivo_pdf, '/storage/')
            || str_contains($this->archivo_pdf, 'archive.org/embed/')
            || str_contains($this->archivo_pdf, 'archive.org/details/')
            || str_ends_with(strtolower(parse_url($this->archivo_pdf, PHP_URL_PATH) ?? ''), '.pdf');
    }

    public function getVisorEmbedUrlAttribute(): ?string
    {
        if (! $this->archivo_pdf) {
            return null;
        }

        if (str_contains($this->archivo_pdf, 'archive.org/details/')) {
            return str_replace('archive.org/details/', 'archive.org/embed/', $this->archivo_pdf);
        }

        return $this->archivo_pdf;
    }

    public function scopeWithVisibleRatingStats(Builder $query): Builder
    {
        return $query
            ->withAvg(['reviews' => fn (Builder $q) => $q->where('visible', true)], 'rating')
            ->withCount(['reviews' => fn (Builder $q) => $q->where('visible', true)]);
    }

    public function scopeFiltro(Builder $query, array $filtros): Builder
    {
        return $query
            ->when(trim((string) ($filtros['buscar'] ?? '')), function (Builder $q, string $buscar) {
                $q->where(function (Builder $sub) use ($buscar) {
                    if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true) && mb_strlen($buscar) >= 3) {
                        $sub->whereFullText(['titulo', 'autor'], $buscar)
                            ->orWhere('titulo', 'like', "%{$buscar}%")
                            ->orWhere('autor', 'like', "%{$buscar}%");
                    } else {
                        $sub->where('titulo', 'like', "%{$buscar}%")
                            ->orWhere('autor', 'like', "%{$buscar}%");
                    }

                    $sub->orWhere('isbn', 'like', "%{$buscar}%")
                        ->orWhere('editorial', 'like', "%{$buscar}%");
                });
            })
            ->when($filtros['category_id'] ?? null, fn (Builder $q, $catId) => $q->where('category_id', $catId))
            ->when($filtros['formato'] ?? null, fn (Builder $q, $formato) => $q->where('formato', $formato))
            ->when($filtros['idioma'] ?? null, fn (Builder $q, $idioma) => $q->where('idioma', $idioma))
            ->when($filtros['licencia'] ?? null, fn (Builder $q, $licencia) => $q->where('licencia', $licencia))
            ->when($filtros['disponibilidad'] ?? null, function (Builder $q, string $disp) {
                if ($disp === 'disponible') {
                    $q->where('stock_disponible', '>', 0);
                } elseif ($disp === 'agotado') {
                    $q->where('stock_disponible', '=', 0);
                }
            });
    }

    public function scopeOrdenar(Builder $query, ?string $orden): Builder
    {
        return match ($orden) {
            'mejor_valorados' => $query->orderByDesc('reviews_avg_rating')->orderByDesc('reviews_count')->orderByDesc('id'),
            'mayor_stock' => $query->orderByDesc('stock_disponible')->orderByDesc('id'),
            default => $query->orderByDesc('id'),
        };
    }
}
