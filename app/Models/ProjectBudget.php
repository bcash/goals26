<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectBudget extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id',
        'project_id',
        'budget_type',
        'budget_total',
        'hourly_rate',
        'estimated_hours',
        'actual_spend',
        'estimated_remaining',
        'burn_rate',
        'alert_threshold_percent',
        'notes',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function percentUsed(): float
    {
        if (!$this->budget_total || $this->budget_total == 0) {
            return 0;
        }

        return round(($this->actual_spend / $this->budget_total) * 100, 1);
    }

    public function isOverBudget(): bool
    {
        return $this->actual_spend > ($this->budget_total ?? PHP_INT_MAX);
    }

    public function isNearAlert(): bool
    {
        return $this->percentUsed() >= $this->alert_threshold_percent;
    }
}
