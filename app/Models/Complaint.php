<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    protected $fillable = [
        'codigo_reclamo',
        'nombre_completo',
        'dni_ce',
        'email',
        'telefono',
        'tipo',
        'detalle',
        'pedido_consumidor',
        'estado',
        'respuesta_admin',
        'respondido_por_id',
        'respondido_at',
    ];

    protected function casts(): array
    {
        return [
            'respondido_at' => 'datetime',
        ];
    }

    public function respondidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'respondido_por_id');
    }
}
