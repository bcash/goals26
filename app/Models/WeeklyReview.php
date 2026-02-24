<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class WeeklyReview extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id',
        'week_start_date',
        'wins',
        'friction',
        'outcomes_met',
        'overall_score',
        'ai_analysis',
        'next_week_focus',
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'outcomes_met'    => 'array',
    ];
}
