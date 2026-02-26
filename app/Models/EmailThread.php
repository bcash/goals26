<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailThread extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_conversation_id',
        'freescout_thread_id',
        'type',
        'body',
        'from_name',
        'from_email',
        'to_emails',
        'cc_emails',
        'has_attachments',
        'attachment_count',
        'ai_quality_score',
        'ai_quality_notes',
        'message_at',
    ];

    protected function casts(): array
    {
        return [
            'to_emails' => 'json',
            'cc_emails' => 'json',
            'has_attachments' => 'boolean',
            'message_at' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(EmailConversation::class, 'email_conversation_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeFromCustomer(Builder $query): Builder
    {
        return $query->where('type', 'customer');
    }

    public function scopeFromAgent(Builder $query): Builder
    {
        return $query->where('type', 'agent');
    }

    public function scopeNotes(Builder $query): Builder
    {
        return $query->where('type', 'note');
    }

    public function scopeChronological(Builder $query): Builder
    {
        return $query->orderBy('message_at');
    }
}
