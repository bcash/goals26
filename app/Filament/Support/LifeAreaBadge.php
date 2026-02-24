<?php

namespace App\Filament\Support;

use App\Models\LifeArea;

class LifeAreaBadge
{
    public static function getOptions(): array
    {
        return LifeArea::orderBy('sort_order')
            ->get()
            ->mapWithKeys(fn($area) => [$area->id => $area->icon . ' ' . $area->name])
            ->toArray();
    }
}
