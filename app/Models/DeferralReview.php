<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeferralReview extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id',
        'deferred_item_id',
        'reviewed_on',
        'outcome',
        'next_revisit_date',
        'review_notes',
        'context_update',
    ];

    protected $casts = [
        'reviewed_on'       => 'date',
        'next_revisit_date' => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function deferredItem(): BelongsTo
    {
        return $this->belongsTo(DeferredItem::class, 'deferred_item_id');
    }
}
