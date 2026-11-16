<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Reader extends Model
{
    protected $fillable = [
        'user_id',
        'dni_codigo',
        'nombres',
        'apellidos',
        'email',
        'telefono',
        'tipo',
        'plan_suscripcion',
        'plan_vence_at',
        'estado',
        'puntos_karma',
    ];

    protected function casts(): array
    {
        return [
            'plan_vence_at' => 'date',
            'puntos_karma' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function waitlists(): HasMany
    {
        return $this->hasMany(Waitlist::class);
    }

    public function karmaMovements(): HasMany
    {
        return $this->hasMany(KarmaMovement::class)->latest('id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }

    public function prestamosActivosCount(): int
    {
        return $this->loans()
            ->whereIn('estado', ['prestado', 'vencido'])
            ->where('estado_pago', '!=', 'anulado')
            ->count();
    }

    public function puedeUsarMembresia(): bool
    {
        if ($this->estado !== 'activo') {
            return false;
        }

        if ($this->plan_suscripcion === 'free') {
            return false;
        }

        return $this->plan_vence_at === null || $this->plan_vence_at->gte(today());
    }

    public function etiquetaPlan(): string
    {
        return match ($this->plan_suscripcion) {
            'pro' => 'Club NovaEther Pro',
            'estudiante' => 'Plan Estudiante',
            default => 'Lector Free',
        };
    }

    public function etiquetaSegmento(): string
    {
        return match ($this->tipo) {
            'estudiante' => 'Estudiante',
            'docente' => 'Docente / Tech',
            default => 'Público General',
        };
    }

    public function nivelKarma(): string
    {
        $pts = (int) $this->puntos_karma;

        if ($pts >= 200) {
            return '🏆 Coleccionista Oro';
        }
        if ($pts >= 100) {
            return '⭐ Lector Constante';
        }

        return '🌱 Miembro Inicial';
    }

    public function registrarKarma(int $puntos, string $motivo, ?string $referenciaTipo = null, ?int $referenciaId = null): void
    {
        if ($puntos === 0) {
            return;
        }

        DB::transaction(function () use ($puntos, $motivo, $referenciaTipo, $referenciaId) {
            /** @var self $locked */
            $locked = self::lockForUpdate()->findOrFail($this->id);

            $nuevoSaldo = max(0, (int) $locked->puntos_karma + $puntos);
            $locked->update(['puntos_karma' => $nuevoSaldo]);

            KarmaMovement::create([
                'reader_id' => $locked->id,
                'puntos' => $puntos,
                'motivo' => $motivo,
                'referencia_tipo' => $referenciaTipo,
                'referencia_id' => $referenciaId,
            ]);

            $this->puntos_karma = $nuevoSaldo;
        });
    }

    public function scopeFiltro(Builder $query, array $filtros): Builder
    {
        return $query
            ->when(trim((string) ($filtros['buscar'] ?? '')), function (Builder $q, string $buscar) {
                $q->where(function (Builder $sub) use ($buscar) {
                    $sub->where('nombres', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('dni_codigo', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%");
                });
            })
            ->when($filtros['tipo'] ?? null, fn (Builder $q, $tipo) => $q->where('tipo', $tipo))
            ->when($filtros['plan_suscripcion'] ?? null, fn (Builder $q, $plan) => $q->where('plan_suscripcion', $plan))
            ->when($filtros['estado'] ?? null, fn (Builder $q, $estado) => $q->where('estado', $estado));
    }
}
