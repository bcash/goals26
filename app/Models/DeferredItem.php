<?php

namespace App\Models;

use App\Traits\HasTenant;
use App\Traits\HasVpoAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DeferredItem extends Model
{
    use HasTenant, HasVpoAccount;

    protected $fillable = [
        'user_id',
        'task_id',
        'project_id',
        'meeting_id',
        'email_conversation_id',
        'scope_item_id',
        'title',
        'description',
        'client_context',
        'why_it_matters',
        'client_name',
        'vpo_account_id',
        'client_quote',
        'deferral_reason',
        'opportunity_type',
        'client_type',
        'estimated_value',
        'value_notes',
        'status',
        'deferred_on',
        'revisit_date',
        'revisit_trigger',
        'last_reviewed_at',
        'review_count',
        'ai_opportunity_analysis',
        'resource_requirements',
        'resource_check_done',
    ];

    protected $casts = [
        'deferred_on' => 'date',
        'revisit_date' => 'date',
        'last_reviewed_at' => 'datetime',
        'resource_requirements' => 'json',
        'resource_check_done' => 'boolean',
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

    public function meetingNote(): BelongsTo
    {
        return $this->belongsTo(MeetingNote::class, 'meeting_id');
    }

    public function emailConversation(): BelongsTo
    {
        return $this->belongsTo(EmailConversation::class);
    }

    public function scopeItem(): BelongsTo
    {
        return $this->belongsTo(MeetingScopeItem::class, 'scope_item_id');
    }

    public function opportunity(): HasOne
    {
        return $this->hasOne(OpportunityPipeline::class, 'deferred_item_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(DeferralReview::class, 'deferred_item_id')
            ->orderByDesc('reviewed_on');
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeDueForReview(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('status', 'scheduled')
                ->where('revisit_date', '<=', today());
        })->orWhere(function ($q) {
            $q->where('status', 'someday')
                ->where(function ($inner) {
                    $inner->whereNull('last_reviewed_at')
                        ->orWhere('last_reviewed_at', '<=', now()->subDays(30));
                });
        });
    }

    public function scopeHasCommercialValue(Builder $query): Builder
    {
        return $query->whereNotIn('opportunity_type', ['none', 'personal-goal'])
            ->whereIn('status', ['someday', 'scheduled', 'in-review', 'promoted']);
    }

    public function scopeByStage(Builder $query, string $stage): Builder
    {
        return $query->whereHas('opportunity', fn ($q) => $q->where('stage', $stage));
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function weightedValue(): float
    {
        if (! $this->opportunity || ! $this->estimated_value) {
            return 0;
        }

        return $this->estimated_value * ($this->opportunity->probability_percent / 100);
    }

    public function isOverdue(): bool
    {
        return $this->revisit_date
            && $this->revisit_date->isPast()
            && in_array($this->status, ['someday', 'scheduled']);
    }

    public function promote(): OpportunityPipeline
    {
        $this->update(['status' => 'promoted', 'has_opportunity' => true]);

        return OpportunityPipeline::create([
            'user_id' => $this->user_id,
            'deferred_item_id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'client_name' => $this->client_name ?? '',
            'estimated_value' => $this->estimated_value,
            'stage' => 'identified',
        ]);
    }
}
