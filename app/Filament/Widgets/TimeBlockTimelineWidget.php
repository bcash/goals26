<?php

namespace App\Filament\Widgets;

use App\Models\DailyPlan;
use App\Models\TimeBlock;

class TimeBlockTimelineWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected string $view = 'filament.widgets.time-block-timeline-widget';

    public function getViewData(): array
    {
        $plan = DailyPlan::today();
        $blocks = $plan
            ? TimeBlock::where('daily_plan_id', $plan->id)
                ->orderBy('start_time')
                ->get()
            : collect();

        return [
            'blocks' => $blocks,
            'hasPlan' => (bool) $plan,
            'editPlanUrl' => $plan
                ? route('filament.admin.resources.daily-plans.edit', $plan)
                : route('filament.admin.resources.daily-plans.create'),
        ];
    }
}
