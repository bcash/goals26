<?php

namespace App\Models;

use App\Traits\HasTenant;
use App\Traits\HasVpoAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpportunityPipeline extends Model
{
    use HasTenant, HasVpoAccount;

    protected $table = 'opportunity_pipeline';

    protected $fillable = [
        'user_id',
        'deferred_item_id',
        'project_id',
        'title',
        'description',
        'client_name',
        'client_email',
        'vpo_account_id',
        'stage',
        'estimated_value',
        'actual_value',
        'probability_percent',
        'expected_close_date',
        'actual_close_date',
        'next_action',
        'next_action_date',
        'notes',
        'lost_reason',
    ];

    protected $casts = [
        'expected_close_date' => 'date',
        'actual_close_date'   => 'date',
        'next_action_date'    => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function deferredItem(): BelongsTo
    {
        return $this->belongsTo(DeferredItem::class, 'deferred_item_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function weightedValue(): float
    {
        return ($this->estimated_value ?? 0) * ($this->probability_percent / 100);
    }

    public function totalPipelineValue(): float
    {
        return static::where('user_id', auth()->id())
            ->whereNotIn('stage', ['closed-won', 'closed-lost'])
            ->get()
            ->sum(fn ($o) => $o->weightedValue());
    }
}
