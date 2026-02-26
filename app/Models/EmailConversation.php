<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailConversation extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'user_id',
        'freescout_conversation_id',
        'freescout_mailbox_id',
        'email_contact_id',
        'project_id',
        'subject',
        'preview',
        'status',
        'type',
        'assigned_to_name',
        'assigned_to_email',
        'tags',
        'thread_count',
        'importance',
        'category',
        'first_message_at',
        'last_message_at',
        'ai_summary',
        'ai_sentiment',
        'ai_priority_score',
        'analysis_status',
        'needs_review',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'json',
            'needs_review' => 'boolean',
            'first_message_at' => 'datetime',
            'last_message_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function contact(): BelongsTo
    {
        return $this->belongsTo(EmailContact::class, 'email_contact_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function threads(): HasMany
    {
        return $this->hasMany(EmailThread::class)->orderBy('message_at');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ConversationNote::class);
    }

    public function deferredItems(): HasMany
    {
        return $this->hasMany(DeferredItem::class, 'email_conversation_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeNeedsReview(Builder $query): Builder
    {
        return $query->where('needs_review', true);
    }

    public function scopeUnanalyzed(Builder $query): Builder
    {
        return $query->where('analysis_status', 'pending');
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('last_message_at', '>=', now()->subDays($days));
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function latestThread(): ?EmailThread
    {
        return $this->threads()->latest('message_at')->first();
    }

    public function agentThreads(): HasMany
    {
        return $this->threads()->where('type', 'agent');
    }

    public function customerThreads(): HasMany
    {
        return $this->threads()->where('type', 'customer');
    }
}
