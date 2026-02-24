<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DailyPlan extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id',
        'plan_date',
        'day_theme',
        'morning_intention',
        'top_priority_1',
        'top_priority_2',
        'top_priority_3',
        'ai_morning_prompt',
        'ai_evening_summary',
        'energy_rating',
        'focus_rating',
        'progress_rating',
        'evening_reflection',
        'status',
    ];

    protected $casts = [
        'plan_date' => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function priority1(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'top_priority_1');
    }

    public function priority2(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'top_priority_2');
    }

    public function priority3(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'top_priority_3');
    }

    public function timeBlocks(): HasMany
    {
        return $this->hasMany(TimeBlock::class)->orderBy('start_time');
    }

    public function aiInteractions(): HasMany
    {
        return $this->hasMany(AiInteraction::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public static function today(): ?self
    {
        return static::whereDate('plan_date', today())->first();
    }

    public static function todayOrCreate(): self
    {
        return static::firstOrCreate(
            ['plan_date' => today()->toDateString()],
            [
                'user_id' => auth()->id(),
                'status'  => 'draft',
            ]
        );
    }
}
