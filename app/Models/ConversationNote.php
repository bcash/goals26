<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversationNote extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'user_id',
        'email_conversation_id',
        'content',
        'note_type',
        'freescout_thread_id',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'synced_at' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(EmailConversation::class, 'email_conversation_id');
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeUnsynced(Builder $query): Builder
    {
        return $query->whereNull('freescout_thread_id')
            ->where('note_type', 'response_draft');
    }
}
