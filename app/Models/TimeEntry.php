<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id',
        'project_id',
        'task_id',
        'description',
        'hours',
        'logged_date',
        'billable',
        'hourly_rate',
        'cost',
    ];

    protected $casts = [
        'logged_date' => 'date',
        'billable'    => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
