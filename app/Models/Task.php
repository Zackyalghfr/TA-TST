<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

// ANGGOTA 1 - tabel tasks
class Task extends Model
{
    public const STATUSES = ['pending', 'in_progress', 'completed'];

    protected $fillable = ['user_id', 'category_id', 'title', 'description', 'status', 'deadline'];

    // Nilai awal yang sama dengan default di migration
    protected $attributes = ['status' => 'pending'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
