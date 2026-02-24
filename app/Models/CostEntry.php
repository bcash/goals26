<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Money\Money;

class CostEntry extends Model
{
    use HasFactory, HasTenant;

    protected $fillable = [
        'user_id',
        'project_id',
        'task_id',
        'client_meeting_id',
        'description',
        'category',
        'amount_cents',
        'currency',
        'duration_minutes',
        'billable',
        'logged_date',
    ];

    protected $casts = [
        'amount_cents' => MoneyCast::class . ':currency',
        'billable'     => 'boolean',
        'logged_date'  => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function clientMeeting(): BelongsTo
    {
        return $this->belongsTo(ClientMeeting::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function amountInDollars(): float
    {
        $raw = $this->getRawOriginal('amount_cents');

        return $raw !== null ? $raw / 100 : 0;
    }
}
