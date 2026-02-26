<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GranolaToken extends Model
{
    protected $fillable = [
        'user_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'scopes',
        'client_id',
        'client_secret',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'client_secret' => 'encrypted',
            'expires_at' => 'datetime',
        ];
    }

    // ── Relationships ─────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Token is expired or will expire within the next 5 minutes.
     */
    public function needsRefresh(): bool
    {
        return $this->expires_at->subMinutes(5)->isPast();
    }
}
