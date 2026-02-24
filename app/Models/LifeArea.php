<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LifeArea extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id',
        'name',
        'icon',
        'color_hex',
        'description',
        'sort_order',
    ];

    // ── Relationships ─────────────────────────────────────────────────

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
}
