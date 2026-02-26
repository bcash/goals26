<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Daily Command Center';

    protected static ?int $navigationSort = -1;

    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'sm' => 2,
            'md' => 3,
            'xl' => 3,
        ];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\DayThemeWidget::class,
            \App\Filament\Widgets\AiIntentionWidget::class,
            \App\Filament\Widgets\MorningChecklistWidget::class,
            \App\Filament\Widgets\UpcomingEventsWidget::class,
            \App\Filament\Widgets\TimeBlockTimelineWidget::class,
            \App\Filament\Widgets\GoalProgressWidget::class,
            \App\Filament\Widgets\HabitRingWidget::class,
            \App\Filament\Widgets\StreakHighlightsWidget::class,
            \App\Filament\Widgets\OpportunityPipelineWidget::class,
            \App\Filament\Widgets\DoneDeliveredWidget::class,
            \App\Filament\Widgets\VpoStatusWidget::class,
            \App\Filament\Widgets\RecentConversationsWidget::class,
            \App\Filament\Widgets\TeamPerformanceWidget::class,
        ];
    }
}
