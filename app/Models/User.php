<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable;

    protected $fillable = ['name', 'email', 'password', 'role'];

    // Data sensitif tidak pernah ikut ke response JSON
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ---- JWT -------------------------------------------------------------

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    // Payload hanya berisi data yang tidak rahasia (id = sub, nama, role)
    public function getJWTCustomClaims(): array
    {
        return [
            'name' => $this->name,
            'role' => $this->role,
        ];
    }

    // ---- Relasi (One to Many) -------------------------------------------

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }
}
