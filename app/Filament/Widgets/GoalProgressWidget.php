<?php

namespace App\Filament\Widgets;

use App\Models\Goal;

class GoalProgressWidget extends BaseWidget
{
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 2;
    protected static string $view = 'filament.widgets.goal-progress-widget';

    public function getViewData(): array
    {
        $goals = Goal::where('status', 'active')
            ->with('lifeArea')
            ->orderBy('life_area_id')
            ->get();

        return ['goals' => $goals];
    }
}
