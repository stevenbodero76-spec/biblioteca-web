<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'rol', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function esAdmin(): bool
    {
        return $this->rol === 'admin';
    }

    public function esCliente(): bool
    {
        return $this->rol === 'cliente';
    }

    public function estaSuspendido(): bool
    {
        return $this->esCliente()
            && $this->reader !== null
            && $this->reader->estado === 'suspendido';
    }

    public function reader(): HasOne
    {
        return $this->hasOne(Reader::class, 'user_id');
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function favoriteBooks(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'favorites')->withTimestamps();
    }

    public function createApiToken(string $deviceName = 'mobile-app'): string
    {
        $plain = Str::random(48);

        $token = $this->apiTokens()->create([
            'name' => Str::limit($deviceName, 100),
            'token' => hash('sha256', $plain),
            'expires_at' => now()->addDays(30),
        ]);

        return "{$token->id}|{$plain}";
    }
}
