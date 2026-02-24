<?php

namespace App\Filament\Widgets;

use App\Models\DailyPlan;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DayThemeWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $plan = DailyPlan::todayOrCreate();
        $yesterday = DailyPlan::whereDate('plan_date', today()->subDay())->first();

        return [
            Stat::make(
                Carbon::today()->format('l, F j'),
                $plan->day_theme ?? 'No theme set'
            )
                ->description('Today\'s theme')
                ->descriptionIcon('heroicon-o-sun')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                    'wire:click' => '$dispatch("open-modal", { id: "set-day-theme" })',
                ]),

            Stat::make('Yesterday\'s Energy', $yesterday?->energy_rating ? $yesterday->energy_rating . ' / 5' : '—')
                ->description('Energy')
                ->color($this->ratingColor($yesterday?->energy_rating)),

            Stat::make('Yesterday\'s Focus', $yesterday?->focus_rating ? $yesterday->focus_rating . ' / 5' : '—')
                ->description('Focus')
                ->color($this->ratingColor($yesterday?->focus_rating)),

            Stat::make('Yesterday\'s Progress', $yesterday?->progress_rating ? $yesterday->progress_rating . ' / 5' : '—')
                ->description('Progress')
                ->color($this->ratingColor($yesterday?->progress_rating)),
        ];
    }

    private function ratingColor(?int $rating): string
    {
        return match (true) {
            $rating === null => 'gray',
            $rating <= 2 => 'danger',
            $rating === 3 => 'warning',
            $rating >= 4 => 'success',
            default => 'gray',
        };
    }
}
