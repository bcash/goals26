<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FreeScoutMailbox extends Model
{
    use HasFactory, HasTenant;

    protected $table = 'freescout_mailboxes';

    protected $fillable = [
        'user_id',
        'freescout_mailbox_id',
        'name',
        'email',
        'life_area_id',
        'sync_enabled',
        'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'sync_enabled' => 'boolean',
            'last_synced_at' => 'datetime',
        ];
    }

    public function lifeArea(): BelongsTo
    {
        return $this->belongsTo(LifeArea::class);
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('sync_enabled', true);
    }
}
