<?php

namespace App\Models;

use App\Traits\HasTenant;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id',
        'entry_date',
        'entry_type',
        'content',
        'mood',
        'tags',
        'ai_insights',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'tags'       => 'array',
    ];
}
