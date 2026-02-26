<?php

namespace App\Models;

use App\Traits\HasTenant;
use App\Traits\HasVpoAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MeetingNote extends Model
{
    use HasFactory, HasTenant, HasVpoAccount;

    protected $table = 'meeting_notes';

    protected $fillable = [
        'user_id',
        'project_id',
        'title',
        'meeting_date',
        'meeting_type',
        'client_type',
        'attendees',
        'transcript',
        'summary',
        'decisions',
        'action_items',
        'ai_scope_analysis',
        'source',
        'vpo_account_id',
        'granola_meeting_id',
        'transcription_status',
        'transcript_received_at',
        'analysis_completed_at',
        'calendar_event_id',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'attendees' => 'array',
        'transcript_received_at' => 'datetime',
        'analysis_completed_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function calendarEvent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class);
    }

    public function scopeItems(): HasMany
    {
        return $this->hasMany(MeetingScopeItem::class, 'meeting_id');
    }

    public function inScopeItems(): HasMany
    {
        return $this->scopeItems()->where('type', 'in-scope');
    }

    public function outOfScopeItems(): HasMany
    {
        return $this->scopeItems()->where('type', 'out-of-scope');
    }

    public function risks(): HasMany
    {
        return $this->scopeItems()->where('type', 'risk');
    }

    public function doneItems(): HasMany
    {
        return $this->hasMany(MeetingDoneItem::class, 'meeting_id');
    }

    public function resourceSignals(): HasMany
    {
        return $this->hasMany(MeetingResourceSignal::class, 'meeting_id');
    }

    public function agenda(): HasOne
    {
        return $this->hasOne(MeetingAgenda::class, 'meeting_note_id');
    }

    public function costEntries(): HasMany
    {
        return $this->hasMany(CostEntry::class, 'meeting_note_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function isSelfMeeting(): bool
    {
        return $this->client_type === 'self';
    }

    public function isAnalyzed(): bool
    {
        return $this->transcription_status === 'complete';
    }

    public function clientLabel(): string
    {
        return $this->isSelfMeeting()
            ? 'Internal — '.(auth()->user()->name ?? 'Me')
            : ($this->project?->client_name ?? 'Unknown Client');
    }
}
