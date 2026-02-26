<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleCalendarConfig extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id',
        'google_calendar_id',
        'calendar_name',
        'life_area_id',
        'sync_enabled',
        'only_with_attendees',
    ];

    protected function casts(): array
    {
        return [
            'sync_enabled' => 'boolean',
            'only_with_attendees' => 'boolean',
        ];
    }

    // -- Relationships -----------------------------------------------------

    public function lifeArea(): BelongsTo
    {
        return $this->belongsTo(LifeArea::class);
    }
}
