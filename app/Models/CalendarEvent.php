<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CalendarEvent extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'user_id',
        'life_area_id',
        'project_id',
        'google_event_id',
        'google_calendar_id',
        'title',
        'description',
        'location',
        'start_at',
        'end_at',
        'all_day',
        'attendees',
        'organizer_email',
        'status',
        'event_type',
        'recurrence_rule',
        'source',
        'synced_at',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'synced_at' => 'datetime',
        'all_day' => 'boolean',
        'attendees' => 'array',
    ];

    // -- Relationships -------------------------------------------------------

    public function lifeArea(): BelongsTo
    {
        return $this->belongsTo(LifeArea::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function agenda(): HasOne
    {
        return $this->hasOne(MeetingAgenda::class, 'calendar_event_id');
    }

    public function meetingNote(): HasOne
    {
        return $this->hasOne(MeetingNote::class, 'calendar_event_id');
    }

    // -- Scopes --------------------------------------------------------------

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_at', '>=', now())
            ->where('status', '!=', 'cancelled')
            ->orderBy('start_at');
    }

    public function scopeForDate(Builder $query, \Carbon\Carbon $date): Builder
    {
        return $query->whereDate('start_at', $date);
    }

    public function scopeFromGoogle(Builder $query): Builder
    {
        return $query->where('source', 'google');
    }

    // -- Helpers -------------------------------------------------------------

    public function isAllDay(): bool
    {
        return $this->all_day;
    }

    public function isPast(): bool
    {
        return $this->end_at->isPast();
    }

    public function hasAgenda(): bool
    {
        return $this->agenda()->exists();
    }

    public function duration(): int
    {
        return (int) $this->start_at->diffInMinutes($this->end_at);
    }
}
