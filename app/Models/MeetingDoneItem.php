<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingDoneItem extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id',
        'meeting_id',
        'task_id',
        'project_id',
        'title',
        'description',
        'outcome',
        'outcome_metric',
        'client_reaction',
        'client_quote',
        'value_delivered',
        'save_as_testimonial',
        'save_for_portfolio',
        'save_for_case_study',
    ];

    protected $casts = [
        'save_as_testimonial' => 'boolean',
        'save_for_portfolio'  => 'boolean',
        'save_for_case_study' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(ClientMeeting::class, 'meeting_id');
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
