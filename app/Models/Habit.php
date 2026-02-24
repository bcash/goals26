<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Habit extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id',
        'life_area_id',
        'title',
        'description',
        'frequency',
        'target_days',
        'time_of_day',
        'status',
        'streak_current',
        'streak_best',
        'started_at',
    ];

    protected $casts = [
        'target_days' => 'array',
        'started_at'  => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function lifeArea(): BelongsTo
    {
        return $this->belongsTo(LifeArea::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(HabitLog::class);
    }

    public function todayLog(): HasOne
    {
        return $this->hasOne(HabitLog::class)
                    ->whereDate('logged_date', today());
    }
}
