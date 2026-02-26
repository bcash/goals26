<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'timezone',
        'subscription_status',
        'trial_ends_at',
        'subscription_ends_at',
        'stripe_customer_id',
        'onboarding_complete',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'onboarding_complete' => 'boolean',
            'password' => 'hashed',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function lifeAreas(): HasMany
    {
        return $this->hasMany(LifeArea::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function habits(): HasMany
    {
        return $this->hasMany(Habit::class);
    }

    public function dailyPlans(): HasMany
    {
        return $this->hasMany(DailyPlan::class);
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function weeklyReviews(): HasMany
    {
        return $this->hasMany(WeeklyReview::class);
    }

    public function aiInteractions(): HasMany
    {
        return $this->hasMany(AiInteraction::class);
    }

    public function meetingNotes(): HasMany
    {
        return $this->hasMany(MeetingNote::class);
    }

    public function deferredItems(): HasMany
    {
        return $this->hasMany(DeferredItem::class);
    }

    public function opportunityPipeline(): HasMany
    {
        return $this->hasMany(OpportunityPipeline::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function granolaToken(): HasOne
    {
        return $this->hasOne(GranolaToken::class);
    }

    public function googleToken(): HasOne
    {
        return $this->hasOne(GoogleToken::class);
    }

    public function googleCalendarConfigs(): HasMany
    {
        return $this->hasMany(GoogleCalendarConfig::class);
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function freescoutMailboxes(): HasMany
    {
        return $this->hasMany(FreeScoutMailbox::class);
    }

    public function emailContacts(): HasMany
    {
        return $this->hasMany(EmailContact::class);
    }

    public function emailConversations(): HasMany
    {
        return $this->hasMany(EmailConversation::class);
    }

    public function conversationNotes(): HasMany
    {
        return $this->hasMany(ConversationNote::class);
    }

    // ── Subscription Helpers ──────────────────────────────────────────

    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial'
            && $this->trial_ends_at
            && $this->trial_ends_at->isFuture();
    }

    public function hasActiveAccess(): bool
    {
        return in_array($this->subscription_status, ['trial', 'active'])
            && ($this->isOnTrial() || (
                $this->subscription_ends_at &&
                $this->subscription_ends_at->isFuture()
            ));
    }

    // ── Integration Helpers ─────────────────────────────────────────

    public function hasGranolaConnection(): bool
    {
        return $this->granolaToken !== null
            && ! $this->granolaToken->isExpired();
    }

    public function hasGoogleConnection(): bool
    {
        return $this->googleToken !== null
            && ! $this->googleToken->isExpired();
    }
}
