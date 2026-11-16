<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'book_id',
        'user_id',
        'rating',
        'comentario',
        'es_spoiler',
        'visible',
        'moderado_por_id',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'es_spoiler' => 'boolean',
            'visible' => 'boolean',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function moderadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderado_por_id');
    }

    public function scopeVisibles(Builder $query): Builder
    {
        return $query->where('visible', true);
    }
}
