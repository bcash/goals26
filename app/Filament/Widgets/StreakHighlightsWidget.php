<?php

namespace App\Filament\Widgets;

use App\Models\Habit;
use App\Models\Milestone;
use Livewire\Attributes\On;

class StreakHighlightsWidget extends BaseWidget
{
    protected static ?int $sort = 7;
    protected int | string | array $columnSpan = 1;
    protected static string $view = 'filament.widgets.streak-highlights-widget';

    #[On('habit-logged')]
    public function refresh(): void
    {
        // Livewire re-renders the component automatically
    }

    public function getViewData(): array
    {
        $streaks = Habit::where('status', 'active')
            ->where('streak_current', '>=', 3)
            ->orderByDesc('streak_current')
            ->limit(5)
            ->with('lifeArea')
            ->get()
            ->map(fn ($habit) => [
                'title' => $habit->title,
                'streak' => $habit->streak_current,
                'best' => $habit->streak_best,
                'color' => $habit->lifeArea?->color_hex ?? '#C9A84C',
                'isPB' => $habit->streak_current >= $habit->streak_best,
            ]);

        $recentMilestones = Milestone::where('status', 'complete')
            ->whereDate('updated_at', '>=', now()->subDays(7))
            ->with('goal.lifeArea')
            ->latest('updated_at')
            ->limit(3)
            ->get();

        return compact('streaks', 'recentMilestones');
    }
}
