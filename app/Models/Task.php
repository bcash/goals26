<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Task extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id',
        'life_area_id',
        'project_id',
        'goal_id',
        'milestone_id',
        'parent_id',
        'depth',
        'path',
        'is_leaf',
        'sort_order',
        'title',
        'notes',
        'plan',
        'context',
        'acceptance_criteria',
        'technical_requirements',
        'dependencies_description',
        'status',
        'priority',
        'due_date',
        'scheduled_date',
        'time_estimate_minutes',
        'estimated_cost',
        'actual_cost',
        'billable',
        'is_daily_action',
        'two_minute_check',
        'decomposition_status',
        'quality_gate_status',
        'deferral_reason',
        'deferral_note',
        'revisit_date',
        'deferral_trigger',
        'has_opportunity',
    ];

    protected $casts = [
        'due_date'         => 'date',
        'scheduled_date'   => 'date',
        'revisit_date'     => 'date',
        'is_daily_action'  => 'boolean',
        'is_leaf'          => 'boolean',
        'billable'         => 'boolean',
        'two_minute_check' => 'boolean',
        'has_opportunity'  => 'boolean',
    ];

    // ── Standard Relationships ────────────────────────────────────────

    public function lifeArea(): BelongsTo
    {
        return $this->belongsTo(LifeArea::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }

    public function milestone(): BelongsTo
    {
        return $this->belongsTo(Milestone::class);
    }

    public function dailyPlan(): BelongsTo
    {
        return $this->belongsTo(DailyPlan::class);
    }

    // ── Tree Relationships ────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('sort_order');
    }

    public function descendants(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')
                    ->where('path', 'like', $this->path . '%');
    }

    public function ancestors(): Collection
    {
        if (!$this->path) {
            return collect();
        }

        $ids = collect(explode('/', trim($this->path, '/')))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === $this->id);

        return Task::withoutGlobalScopes()
            ->whereIn('id', $ids)
            ->orderBy('depth')
            ->get();
    }

    // ── Quality Gate ──────────────────────────────────────────────────

    public function qualityGates(): HasMany
    {
        return $this->hasMany(TaskQualityGate::class);
    }

    public function activeQualityGate(): ?TaskQualityGate
    {
        return $this->qualityGates()->where('status', 'pending')->latest()->first();
    }

    // ── Cost Tracking ─────────────────────────────────────────────────

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function costEntries(): HasMany
    {
        return $this->hasMany(CostEntry::class);
    }

    public function totalLoggedHours(): float
    {
        return $this->timeEntries()->sum('hours');
    }

    // ── Deferral ──────────────────────────────────────────────────────

    public function deferredItem(): HasOne
    {
        return $this->hasOne(DeferredItem::class);
    }

    public function deferredItems(): HasMany
    {
        return $this->hasMany(DeferredItem::class);
    }

    // ── Tree State Helpers ────────────────────────────────────────────

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function isLeaf(): bool
    {
        return $this->is_leaf;
    }

    public function isReadyToSchedule(): bool
    {
        return $this->is_leaf
            && $this->two_minute_check
            && $this->decomposition_status === 'ready';
    }

    public function getPath(): string
    {
        return $this->path ?? '';
    }

    public function completionPercent(): int
    {
        $leaves = $this->allLeaves();
        if ($leaves->isEmpty()) {
            return 0;
        }

        $done = $leaves->where('status', 'done')->count();

        return (int) round(($done / $leaves->count()) * 100);
    }

    public function allLeaves(): Collection
    {
        return Task::where('path', 'like', $this->path . '%')
                   ->where('is_leaf', true)
                   ->get();
    }

    public function siblingsComplete(): bool
    {
        if (!$this->parent_id) {
            return false;
        }

        return Task::where('parent_id', $this->parent_id)
                   ->where('id', '!=', $this->id)
                   ->where('status', '!=', 'done')
                   ->doesntExist();
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeActionable(Builder $query): Builder
    {
        return $query->where('is_leaf', true)
                     ->where('two_minute_check', true)
                     ->whereIn('status', ['todo', 'in-progress']);
    }

    public function scopeNeedsBreakdown(Builder $query): Builder
    {
        return $query->where('decomposition_status', 'needs_breakdown')
                     ->where('is_leaf', true)
                     ->orderBy('depth', 'desc');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_date')
                     ->where('due_date', '<', today())
                     ->whereIn('status', ['todo', 'in-progress']);
    }

    public function scopeDeferred(Builder $query): Builder
    {
        return $query->where('status', 'deferred');
    }

    public function scopeDailyActions(Builder $query): Builder
    {
        return $query->where('is_daily_action', true)
                     ->whereIn('status', ['todo', 'in-progress']);
    }

    public function scopeLeaves(Builder $query): Builder
    {
        return $query->where('is_leaf', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}
