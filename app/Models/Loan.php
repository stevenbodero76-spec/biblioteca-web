<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Loan extends Model
{
    protected $fillable = [
        'book_id',
        'reader_id',
        'user_id',
        'fecha_prestamo',
        'fecha_devolucion_esperada',
        'fecha_devolucion_real',
        'estado',
        'metodo_pago',
        'monto',
        'estado_pago',
        'codigo_operacion',
        'fecha_pago',
        'expira_pago_at',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_prestamo' => 'date',
            'fecha_devolucion_esperada' => 'date',
            'fecha_devolucion_real' => 'date',
            'fecha_pago' => 'datetime',
            'expira_pago_at' => 'datetime',
            'monto' => 'decimal:2',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orderItem(): HasOne
    {
        return $this->hasOne(OrderItem::class);
    }

    public function estaAnulado(): bool
    {
        return $this->estado_pago === 'anulado';
    }

    public function pagoPendiente(): bool
    {
        return $this->estado_pago === 'pendiente' && ! $this->estaAnulado();
    }

    public function estaVencido(): bool
    {
        if ($this->estado === 'devuelto' || $this->estaAnulado()) {
            return false;
        }

        return $this->estado === 'vencido'
            || ($this->fecha_devolucion_esperada !== null && $this->fecha_devolucion_esperada->lt(today()));
    }

    public function etiquetaMetodoPago(): string
    {
        return match ($this->metodo_pago) {
            'yape_plin' => 'Yape / Plin (Reserva Digital)',
            'mostrador' => 'Pago en Mostrador al Recoger',
            default => 'Incluido en Club NovaEther',
        };
    }

    public function etiquetaEstadoPago(): string
    {
        return match ($this->estado_pago) {
            'pagado' => 'Pago Verificado',
            'anulado' => 'Anulado / Expirado',
            default => 'Pendiente de Validación',
        };
    }

    public function codigoTicket(): string
    {
        $firma = strtoupper(substr(hash_hmac('sha256', "{$this->id}|{$this->book_id}|{$this->reader_id}", (string) config('app.key')), 0, 8));

        return sprintf('NV-%05d-%s', $this->id, $firma);
    }

    public function scopeVisiblePara(Builder $query, User $user): Builder
    {
        if ($user->esAdmin()) {
            return $query;
        }

        $readerId = $user->reader?->id;

        if (! $readerId) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('reader_id', $readerId);
    }

    public function scopeFiltro(Builder $query, array $filtros): Builder
    {
        return $query
            ->when(trim((string) ($filtros['buscar'] ?? '')), function (Builder $q, string $buscar) {
                $q->where(function (Builder $sub) use ($buscar) {
                    $sub->where('codigo_operacion', 'like', "%{$buscar}%")
                        ->orWhereHas('book', fn (Builder $b) => $b->where('titulo', 'like', "%{$buscar}%")->orWhere('isbn', 'like', "%{$buscar}%"))
                        ->orWhereHas('reader', fn (Builder $r) => $r->where('nombres', 'like', "%{$buscar}%")->orWhere('apellidos', 'like', "%{$buscar}%")->orWhere('dni_codigo', 'like', "%{$buscar}%"));
                });
            })
            ->when($filtros['estado'] ?? null, fn (Builder $q, $estado) => $q->where('estado', $estado))
            ->when($filtros['estado_pago'] ?? null, fn (Builder $q, $pago) => $q->where('estado_pago', $pago));
    }
}
