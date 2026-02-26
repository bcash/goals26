<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostEntry extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'user_id',
        'project_id',
        'task_id',
        'meeting_note_id',
        'description',
        'category',
        'amount_cents',
        'currency',
        'duration_minutes',
        'billable',
        'logged_date',
    ];

    protected $casts = [
        'amount_cents' => MoneyCast::class.':currency',
        'billable' => 'boolean',
        'logged_date' => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function meetingNote(): BelongsTo
    {
        return $this->belongsTo(MeetingNote::class, 'meeting_note_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function amountInDollars(): float
    {
        $raw = $this->getRawOriginal('amount_cents');

        return $raw !== null ? $raw / 100 : 0;
    }
}
