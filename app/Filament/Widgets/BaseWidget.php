<?php

namespace App\Filament\Widgets;

use App\Models\DailyPlan;
use App\Models\User;
use Filament\Widgets\Widget;

abstract class BaseWidget extends Widget
{
    protected function currentUser(): User
    {
        return auth()->user();
    }

    protected function todayPlan(): ?DailyPlan
    {
        return DailyPlan::today();
    }
}
