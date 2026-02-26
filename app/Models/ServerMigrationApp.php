<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ServerMigrationApp extends Model
{
    protected $fillable = [
        'cloudways_app_id',
        'app_label',
        'app_cname',
        'domains',
        'primary_domain',
        'category',
        'should_migrate',
        'status',
        'target_app_id',
        'clone_operation_id',
        'clone_started_at',
        'clone_completed_at',
        'dns_records_updated',
        'dns_switched_at',
        'ssl_installed',
        'ssl_installed_at',
        'verified',
        'http_status_code',
        'verification_notes',
        'verified_at',
        'last_error',
        'retry_count',
    ];

    protected function casts(): array
    {
        return [
            'domains' => 'json',
            'dns_records_updated' => 'json',
            'should_migrate' => 'boolean',
            'ssl_installed' => 'boolean',
            'verified' => 'boolean',
            'clone_started_at' => 'datetime',
            'clone_completed_at' => 'datetime',
            'dns_switched_at' => 'datetime',
            'ssl_installed_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeMigratable(Builder $query): Builder
    {
        return $query->where('should_migrate', true);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function markStatus(string $status, ?string $error = null): void
    {
        $update = ['status' => $status];

        if ($error) {
            $update['last_error'] = $error;
        }

        $this->update($update);
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'last_error' => $error,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['completed', 'failed']);
    }

    public function canClone(): bool
    {
        return $this->should_migrate && in_array($this->status, ['pending', 'failed']);
    }

    public function canSwitchDns(): bool
    {
        return in_array($this->status, ['cloned', 'failed']);
    }

    public function canVerify(): bool
    {
        return in_array($this->status, ['dns_switched', 'verified', 'failed']);
    }
}
