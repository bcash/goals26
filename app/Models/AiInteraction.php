<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiInteraction extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id',
        'interaction_type',
        'context_json',
        'prompt',
        'response',
        'daily_plan_id',
        'goal_id',
        'tokens_used',
        'model_used',
    ];

    protected $casts = [
        'context_json' => 'array',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function dailyPlan(): BelongsTo
    {
        return $this->belongsTo(DailyPlan::class);
    }

    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }
}
