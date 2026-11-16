<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KarmaMovement extends Model
{
    protected $fillable = [
        'reader_id',
        'puntos',
        'motivo',
        'referencia_tipo',
        'referencia_id',
    ];

    protected function casts(): array
    {
        return [
            'puntos' => 'integer',
            'referencia_id' => 'integer',
        ];
    }

    public function reader(): BelongsTo
    {
        return $this->belongsTo(Reader::class);
    }
}
