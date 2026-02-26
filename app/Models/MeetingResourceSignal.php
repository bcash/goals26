<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingResourceSignal extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id',
        'meeting_id',
        'deferred_item_id',
        'resource_type',
        'description',
        'client_quote',
        'constraint_timeline',
        'creates_revisit_opportunity',
    ];

    protected $casts = [
        'creates_revisit_opportunity' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function meetingNote(): BelongsTo
    {
        return $this->belongsTo(MeetingNote::class, 'meeting_id');
    }

    public function deferredItem(): BelongsTo
    {
        return $this->belongsTo(DeferredItem::class);
    }
}
