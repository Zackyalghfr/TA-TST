<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// ANGGOTA 4 - tabel schedules
class Schedule extends Model
{
    protected $fillable = ['user_id', 'task_id', 'schedule_date', 'start_time', 'end_time'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
