<?php

namespace App\Models;

use App\Traits\HasTenant;
use App\Traits\HasVpoAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailContact extends Model
{
    use HasFactory, HasTenant, HasVpoAccount;

    protected $fillable = [
        'user_id',
        'freescout_customer_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'company',
        'job_title',
        'contact_type',
        'vpo_account_id',
        'vpo_contact_id',
        'project_id',
        'notes',
        'tags',
        'first_contact_at',
        'last_contact_at',
        'conversation_count',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'json',
            'first_contact_at' => 'datetime',
            'last_contact_at' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(EmailConversation::class);
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('contact_type', $type);
    }

    public function scopeWithRecentActivity(Builder $query, int $days = 30): Builder
    {
        return $query->where('last_contact_at', '>=', now()->subDays($days));
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function fullName(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? '')) ?: $this->email;
    }

    public function displayName(): string
    {
        $name = $this->fullName();

        return $this->company ? "{$name} ({$this->company})" : $name;
    }
}
