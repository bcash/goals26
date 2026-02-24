<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HabitLog extends Model
{
    protected $fillable = [
        'habit_id',
        'logged_date',
        'status',
        'note',
    ];

    protected $casts = [
        'logged_date' => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function habit(): BelongsTo
    {
        return $this->belongsTo(Habit::class);
    }
}
