<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeBlock extends Model
{
    protected $fillable = [
        'daily_plan_id',
        'title',
        'block_type',
        'start_time',
        'end_time',
        'task_id',
        'project_id',
        'notes',
        'color_hex',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function dailyPlan(): BelongsTo
    {
        return $this->belongsTo(DailyPlan::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
