<?php

namespace App\Filament\Widgets;

use App\Models\DailyPlan;
use App\Models\Habit;
use App\Models\HabitLog;
use App\Services\HabitStreakService;
use Livewire\Attributes\On;

class MorningChecklistWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 2;
    protected static string $view = 'filament.widgets.morning-checklist-widget';

    public ?DailyPlan $plan = null;
    public array $habitStatuses = [];

    public function mount(): void
    {
        $this->plan = DailyPlan::todayOrCreate();
        $this->loadHabitStatuses();
    }

    protected function loadHabitStatuses(): void
    {
        $habits = Habit::where('status', 'active')
            ->with(['todayLog'])
            ->orderBy('time_of_day')
            ->get();

        $this->habitStatuses = $habits->map(fn ($habit) => [
            'id' => $habit->id,
            'title' => $habit->title,
            'time_of_day' => $habit->time_of_day,
            'life_area' => $habit->lifeArea?->name,
            'color' => $habit->lifeArea?->color_hex,
            'completed' => $habit->todayLog?->status === 'completed',
            'log_id' => $habit->todayLog?->id,
        ])->toArray();
    }

    public function togglePriority(int $taskId): void
    {
        $task = \App\Models\Task::findOrFail($taskId);
        $task->update([
            'status' => $task->status === 'done' ? 'todo' : 'done',
        ]);
        $this->plan->refresh();
    }

    public function toggleHabit(int $habitId): void
    {
        $habit = Habit::findOrFail($habitId);
        $log = HabitLog::firstOrNew([
            'habit_id' => $habitId,
            'logged_date' => today(),
        ]);

        if ($log->exists && $log->status === 'completed') {
            $log->update(['status' => 'skipped']);
        } else {
            $log->fill(['status' => 'completed'])->save();
            if (class_exists(HabitStreakService::class)) {
                app(HabitStreakService::class)->recalculate($habit);
            }
        }

        $this->loadHabitStatuses();
        $this->dispatch('habit-logged');
    }

    public function getViewData(): array
    {
        return [
            'plan' => $this->plan,
            'priority1' => $this->plan?->priority1,
            'priority2' => $this->plan?->priority2,
            'priority3' => $this->plan?->priority3,
            'habitStatuses' => $this->habitStatuses,
            'morningDone' => $this->isMorningComplete(),
        ];
    }

    private function isMorningComplete(): bool
    {
        return $this->plan?->day_theme
            && $this->plan?->morning_intention
            && $this->plan?->top_priority_1;
    }
}
