<?php

namespace App\Models;

use App\Traits\HasTenant;
use App\Traits\HasVpoAccount;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MeetingAgenda extends Model
{
    use HasTenant, HasVpoAccount;

    protected $fillable = [
        'user_id',
        'project_id',
        'client_meeting_id',
        'title',
        'client_type',
        'client_name',
        'vpo_account_id',
        'scheduled_for',
        'purpose',
        'desired_outcomes',
        'status',
        'ai_suggested_topics',
        'notes',
    ];

    protected $casts = [
        'scheduled_for'       => 'datetime',
        'desired_outcomes'    => 'array',
        'ai_suggested_topics' => 'array',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function items(): HasMany
    {
        return $this->hasMany(AgendaItem::class, 'agenda_id')->orderBy('sort_order');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(ClientMeeting::class, 'client_meeting_id');
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function previousMeetings(): Collection
    {
        if (!$this->project_id) {
            return new Collection();
        }

        return ClientMeeting::where('project_id', $this->project_id)
            ->where('meeting_date', '<', $this->scheduled_for ?? now())
            ->orderByDesc('meeting_date')
            ->get();
    }

    public function openActionItems(): Collection
    {
        if (!$this->project_id) {
            return new Collection();
        }

        return Task::where('project_id', $this->project_id)
            ->whereIn('status', ['todo', 'in-progress'])
            ->orderBy('priority', 'desc')
            ->get();
    }

    public function deferredItemsForReview(): Collection
    {
        if (!$this->project_id) {
            return new Collection();
        }

        return DeferredItem::where('project_id', $this->project_id)
            ->dueForReview()
            ->orderByDesc('estimated_value')
            ->get();
    }
}
