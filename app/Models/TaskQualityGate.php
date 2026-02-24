<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskQualityGate extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id',
        'task_id',
        'triggered_at',
        'reviewed_at',
        'status',
        'checklist',
        'reviewer_notes',
        'failure_reason',
        'children_completed',
        'children_total',
    ];

    protected $casts = [
        'triggered_at' => 'datetime',
        'reviewed_at'  => 'datetime',
        'checklist'    => 'array',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
