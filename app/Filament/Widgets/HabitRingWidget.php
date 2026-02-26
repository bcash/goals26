<?php

namespace App\Filament\Widgets;

use App\Models\Habit;
use Livewire\Attributes\On;

class HabitRingWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    protected string $view = 'filament.widgets.habit-ring-widget';

    #[On('habit-logged')]
    public function refresh(): void
    {
        // Livewire re-renders the component automatically
    }

    public function getViewData(): array
    {
        $habits = Habit::where('status', 'active')->with('todayLog')->get();
        $total = $habits->count();
        $completed = $habits->filter(fn ($h) => $h->todayLog?->status === 'completed')->count();
        $percent = $total > 0 ? round(($completed / $total) * 100) : 0;

        // Circumference of the SVG ring: r=36 -> C = 2*pi*36 ~ 226.2
        $circumference = 226.2;
        $dashOffset = $circumference - ($circumference * $percent / 100);

        $topStreaks = Habit::where('status', 'active')
            ->where('streak_current', '>', 0)
            ->orderByDesc('streak_current')
            ->limit(3)
            ->get(['title', 'streak_current', 'life_area_id'])
            ->map(fn ($h) => [
                'title' => $h->title,
                'streak' => $h->streak_current,
            ]);

        return compact('total', 'completed', 'percent', 'dashOffset', 'circumference', 'topStreaks');
    }
}
