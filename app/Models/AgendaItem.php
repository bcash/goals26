<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaItem extends Model
{
    protected $fillable = [
        'agenda_id',
        'title',
        'description',
        'item_type',
        'source_type',
        'source_id',
        'time_allocation_minutes',
        'sort_order',
        'status',
        'outcome_notes',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function meetingAgenda(): BelongsTo
    {
        return $this->belongsTo(MeetingAgenda::class, 'agenda_id');
    }
}
