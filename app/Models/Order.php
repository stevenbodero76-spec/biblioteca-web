<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'codigo_orden',
        'user_id',
        'reader_id',
        'metodo_pago',
        'estado_pago',
        'estado_pedido',
        'subtotal',
        'descuento_karma',
        'puntos_karma_usados',
        'total',
        'codigo_operacion',
        'tipo_entrega',
        'direccion_entrega',
        'fecha_pago',
        'expira_pago_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'descuento_karma' => 'decimal:2',
            'puntos_karma_usados' => 'integer',
            'total' => 'decimal:2',
            'fecha_pago' => 'datetime',
            'expira_pago_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function scopeVisiblePara(Builder $query, User $user): Builder
    {
        return $user->esAdmin() ? $query : $query->where('user_id', $user->id);
    }

    public function etiquetaMetodoPago(): string
    {
        return match ($this->metodo_pago) {
            'yape_plin' => 'Yape / Plin QR',
            'tarjeta' => 'Tarjeta (Pasarela Sandbox)',
            'mostrador' => 'Pago en Mostrador',
            default => 'Club NovaEther',
        };
    }

    public function etiquetaEstadoPago(): string
    {
        return match ($this->estado_pago) {
            'pagado' => 'Pagado',
            'anulado' => 'Anulado',
            default => 'Pendiente de Validación',
        };
    }

    public function etiquetaEstadoPedido(): string
    {
        return match ($this->estado_pedido) {
            'confirmado' => 'Confirmado',
            'entregado' => 'Entregado',
            'cancelado' => 'Cancelado',
            default => 'Pendiente',
        };
    }
}
