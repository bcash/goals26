<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Traits\HasTenant;
use App\Traits\HasVpoAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Project extends Model
{
    use HasTenant, HasVpoAccount;

    protected $fillable = [
        'user_id',
        'life_area_id',
        'goal_id',
        'name',
        'description',
        'tech_stack',
        'architecture_notes',
        'export_template',
        'status',
        'client_name',
        'vpo_account_id',
        'due_date',
        'color_hex',
        'budget_cents',
        'budget_currency',
    ];

    protected $casts = [
        'due_date' => 'date',
        'budget_cents' => MoneyCast::class.':budget_currency',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function lifeArea(): BelongsTo
    {
        return $this->belongsTo(LifeArea::class);
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function meetingNotes(): HasMany
    {
        return $this->hasMany(MeetingNote::class);
    }

    public function budget(): HasOne
    {
        return $this->hasOne(ProjectBudget::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(ProjectBudget::class);
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function costEntries(): HasMany
    {
        return $this->hasMany(CostEntry::class);
    }
}
