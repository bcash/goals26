<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingScopeItem extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id',
        'meeting_id',
        'task_id',
        'description',
        'type',
        'confirmed_with_client',
        'client_quote',
        'notes',
    ];

    protected $casts = [
        'confirmed_with_client' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function meetingNote(): BelongsTo
    {
        return $this->belongsTo(MeetingNote::class, 'meeting_id');
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
