# SOLAS RÚN
### *Dashboard Widgets*
**Technical Reference v1.1**

---

## Overview

The Solas Rún dashboard is the first thing you see every morning and the last thing you close every evening. It is built entirely from Filament widgets — each one a focused, purposeful view into a single aspect of your day. Together they form the **Daily Command Center**.

All widgets are scoped to the authenticated user automatically via the `HasTenant` trait on their underlying models.

---

## Table of Contents

1. [Widget Architecture](#1-widget-architecture)
2. [DayThemeWidget](#2-daythemewidget)
3. [MorningChecklistWidget](#3-morningchecklistwidget)
4. [TimeBlockTimelineWidget](#4-timeblocktimelinewidget)
5. [GoalProgressWidget](#5-goalprogresswidget)
6. [HabitRingWidget](#6-habitringwidget)
7. [AiIntentionWidget](#7-aiintentionwidget)
8. [StreakHighlightsWidget](#8-streakhighlightswidget)
9. [DoneDeliveredWidget](#9-donedeliveredwidget)
10. [Dashboard Layout & Registration](#10-dashboard-layout--registration)
11. [Shared Widget Styles](#11-shared-widget-styles)

---

## 1. Widget Architecture

### Filament Widget Types Used

| Widget Type | Used For |
|-------------|----------|
| `StatsOverviewWidget` | Day theme, daily ratings summary |
| `Widget` (custom Blade) | Morning checklist, time block timeline, habit ring, AI intention |
| `ChartWidget` | Goal progress bars, streak highlights |

### Generate All Widgets

```bash
php artisan make:filament-widget DayThemeWidget
php artisan make:filament-widget MorningChecklistWidget
php artisan make:filament-widget TimeBlockTimelineWidget
php artisan make:filament-widget GoalProgressWidget --chart
php artisan make:filament-widget HabitRingWidget --chart
php artisan make:filament-widget AiIntentionWidget
php artisan make:filament-widget StreakHighlightsWidget
```

### Widget Base Class

All custom Blade widgets extend `\Filament\Widgets\Widget`. Create a shared base to hold common helpers:

```php
// app/Filament/Widgets/BaseWidget.php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

abstract class BaseWidget extends Widget
{
    protected function currentUser(): \App\Models\User
    {
        return auth()->user();
    }

    protected function todayPlan(): ?\App\Models\DailyPlan
    {
        return \App\Models\DailyPlan::today();
    }
}
```

---

## 2. DayThemeWidget

Displays today's date, the day theme if set, and last night's ratings at a glance. The entry point to the dashboard — always at the top.

### Class

```php
// app/Filament/Widgets/DayThemeWidget.php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\DailyPlan;
use Carbon\Carbon;

class DayThemeWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $plan    = DailyPlan::todayOrCreate();
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
                ->description('⚡ Energy')
                ->color($this->ratingColor($yesterday?->energy_rating)),

            Stat::make('Yesterday\'s Focus', $yesterday?->focus_rating ? $yesterday->focus_rating . ' / 5' : '—')
                ->description('🎯 Focus')
                ->color($this->ratingColor($yesterday?->focus_rating)),

            Stat::make('Yesterday\'s Progress', $yesterday?->progress_rating ? $yesterday->progress_rating . ' / 5' : '—')
                ->description('📈 Progress')
                ->color($this->ratingColor($yesterday?->progress_rating)),
        ];
    }

    private function ratingColor(?int $rating): string
    {
        return match(true) {
            $rating === null  => 'gray',
            $rating <= 2      => 'danger',
            $rating === 3     => 'warning',
            $rating >= 4      => 'success',
            default           => 'gray',
        };
    }
}
```

---

## 3. MorningChecklistWidget

The heart of the morning session. Displays the Top 3 priorities, today's habit checklist, and morning session completion status. Fully interactive — check off tasks and habits directly from the dashboard.

### Class

```php
// app/Filament/Widgets/MorningChecklistWidget.php

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
            'id'          => $habit->id,
            'title'       => $habit->title,
            'time_of_day' => $habit->time_of_day,
            'life_area'   => $habit->lifeArea?->name,
            'color'       => $habit->lifeArea?->color_hex,
            'completed'   => $habit->todayLog?->status === 'completed',
            'log_id'      => $habit->todayLog?->id,
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
        $log   = HabitLog::firstOrNew([
            'habit_id'    => $habitId,
            'logged_date' => today(),
        ]);

        if ($log->exists && $log->status === 'completed') {
            $log->update(['status' => 'skipped']);
        } else {
            $log->fill(['status' => 'completed'])->save();
            app(HabitStreakService::class)->recalculate($habit);
        }

        $this->loadHabitStatuses();
    }

    public function getViewData(): array
    {
        return [
            'plan'          => $this->plan,
            'priority1'     => $this->plan?->priority1,
            'priority2'     => $this->plan?->priority2,
            'priority3'     => $this->plan?->priority3,
            'habitStatuses' => $this->habitStatuses,
            'morningDone'   => $this->isMorningComplete(),
        ];
    }

    private function isMorningComplete(): bool
    {
        return $this->plan?->day_theme
            && $this->plan?->morning_intention
            && $this->plan?->top_priority_1;
    }
}
```

### Blade View

```blade
{{-- resources/views/filament/widgets/morning-checklist-widget.blade.php --}}

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            🌅 Morning Checklist
            @if($morningDone)
                <span class="text-sm font-normal text-success-500 ml-2">✓ Morning session complete</span>
            @endif
        </x-slot>

        {{-- Top 3 Priorities --}}
        <div class="mb-6">
            <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
                Top 3 Priorities
            </h4>

            @forelse([$priority1, $priority2, $priority3] as $i => $task)
                @if($task)
                    <div class="flex items-center gap-3 p-3 rounded-lg mb-2
                        {{ $task->status === 'done' ? 'bg-success-50 dark:bg-success-950' : 'bg-gray-50 dark:bg-gray-900' }}"
                    >
                        <button
                            wire:click="togglePriority({{ $task->id }})"
                            class="flex-shrink-0 w-6 h-6 rounded-full border-2
                                {{ $task->status === 'done'
                                    ? 'bg-success-500 border-success-500 text-white'
                                    : 'border-gray-300 hover:border-primary-500' }}
                                flex items-center justify-center transition-colors"
                        >
                            @if($task->status === 'done')
                                <x-heroicon-s-check class="w-3 h-3" />
                            @endif
                        </button>

                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium {{ $task->status === 'done' ? 'line-through text-gray-400' : 'text-gray-900 dark:text-white' }}">
                                {{ $loop->iteration }}. {{ $task->title }}
                            </p>
                            @if($task->project)
                                <p class="text-xs text-gray-400 truncate">{{ $task->project->name }}</p>
                            @endif
                        </div>

                        <span class="text-xs px-2 py-1 rounded-full
                            {{ match($task->priority) {
                                'critical' => 'bg-danger-100 text-danger-700',
                                'high'     => 'bg-warning-100 text-warning-700',
                                default    => 'bg-gray-100 text-gray-500',
                            } }}">
                            {{ ucfirst($task->priority) }}
                        </span>
                    </div>
                @endif
            @empty
                <div class="text-center py-6 text-gray-400">
                    <x-heroicon-o-clipboard-document-list class="w-8 h-8 mx-auto mb-2" />
                    <p class="text-sm">No priorities set for today.</p>
                    <a href="{{ route('filament.admin.resources.daily-plans.edit', DailyPlan::todayOrCreate()) }}"
                       class="text-primary-500 text-sm hover:underline">Set your Top 3 →</a>
                </div>
            @endforelse
        </div>

        {{-- Habit Checklist --}}
        <div>
            <h4 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">
                Today's Habits
                <span class="ml-2 text-xs font-normal text-gray-400">
                    {{ collect($habitStatuses)->where('completed', true)->count() }}
                    / {{ count($habitStatuses) }}
                </span>
            </h4>

            @forelse($habitStatuses as $habit)
                <div class="flex items-center gap-3 p-2 rounded-lg mb-1
                    {{ $habit['completed'] ? 'opacity-60' : '' }} hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                >
                    <button
                        wire:click="toggleHabit({{ $habit['id'] }})"
                        class="flex-shrink-0 w-5 h-5 rounded border-2 transition-colors
                            {{ $habit['completed']
                                ? 'bg-success-500 border-success-500'
                                : 'border-gray-300 hover:border-primary-400' }}"
                    >
                        @if($habit['completed'])
                            <x-heroicon-s-check class="w-3 h-3 text-white mx-auto" />
                        @endif
                    </button>

                    <div
                        class="w-2 h-2 rounded-full flex-shrink-0"
                        style="background-color: {{ $habit['color'] ?? '#C9A84C' }}"
                    ></div>

                    <span class="text-sm {{ $habit['completed'] ? 'line-through text-gray-400' : 'text-gray-700 dark:text-gray-300' }}">
                        {{ $habit['title'] }}
                    </span>

                    <span class="ml-auto text-xs text-gray-400">
                        {{ match($habit['time_of_day']) {
                            'morning'   => '🌅',
                            'afternoon' => '☀️',
                            'evening'   => '🌙',
                            default     => '',
                        } }}
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-400 py-2">No habits scheduled for today.</p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
```

---

## 4. TimeBlockTimelineWidget

A visual timeline of today's scheduled time blocks. Color-coded by block type, showing the day's architecture at a glance.

### Class

```php
// app/Filament/Widgets/TimeBlockTimelineWidget.php

namespace App\Filament\Widgets;

use App\Models\DailyPlan;
use App\Models\TimeBlock;

class TimeBlockTimelineWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 1;
    protected static string $view = 'filament.widgets.time-block-timeline-widget';

    public function getViewData(): array
    {
        $plan   = DailyPlan::today();
        $blocks = $plan
            ? TimeBlock::where('daily_plan_id', $plan->id)
                       ->orderBy('start_time')
                       ->get()
            : collect();

        return [
            'blocks'      => $blocks,
            'hasPlan'     => (bool) $plan,
            'editPlanUrl' => $plan
                ? route('filament.admin.resources.daily-plans.edit', $plan)
                : route('filament.admin.resources.daily-plans.create'),
        ];
    }
}
```

### Blade View

```blade
{{-- resources/views/filament/widgets/time-block-timeline-widget.blade.php --}}

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">🕐 Today's Schedule</x-slot>
        <x-slot name="headerEnd">
            <a href="{{ $editPlanUrl }}" class="text-xs text-primary-500 hover:underline">
                Edit Plan →
            </a>
        </x-slot>

        @if($blocks->isEmpty())
            <div class="text-center py-8 text-gray-400">
                <x-heroicon-o-clock class="w-8 h-8 mx-auto mb-2" />
                <p class="text-sm">No time blocks scheduled.</p>
                <a href="{{ $editPlanUrl }}" class="text-primary-500 text-sm hover:underline">
                    Build your schedule →
                </a>
            </div>
        @else
            <div class="space-y-1">
                @foreach($blocks as $block)
                    @php
                        $colors = [
                            'deep-work' => ['bg' => 'bg-success-100 dark:bg-success-950',  'bar' => 'bg-success-500',  'text' => 'text-success-700 dark:text-success-400'],
                            'admin'     => ['bg' => 'bg-gray-100 dark:bg-gray-800',         'bar' => 'bg-gray-400',     'text' => 'text-gray-600 dark:text-gray-400'],
                            'meeting'   => ['bg' => 'bg-warning-100 dark:bg-warning-950',   'bar' => 'bg-warning-500',  'text' => 'text-warning-700 dark:text-warning-400'],
                            'personal'  => ['bg' => 'bg-info-100 dark:bg-info-950',         'bar' => 'bg-info-500',     'text' => 'text-info-700 dark:text-info-400'],
                            'buffer'    => ['bg' => 'bg-gray-50 dark:bg-gray-900',          'bar' => 'bg-gray-300',     'text' => 'text-gray-500'],
                        ];
                        $c = $colors[$block->block_type] ?? $colors['admin'];
                        $isNow = now()->between(
                            \Carbon\Carbon::parse($block->start_time),
                            \Carbon\Carbon::parse($block->end_time)
                        );
                    @endphp

                    <div class="flex items-stretch gap-2 rounded-lg {{ $c['bg'] }} {{ $isNow ? 'ring-2 ring-primary-500' : '' }} px-3 py-2">
                        <div class="w-1 rounded-full flex-shrink-0 {{ $c['bar'] }}"></div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium {{ $c['text'] }} truncate">
                                    {{ $block->title }}
                                    @if($isNow)
                                        <span class="ml-1 text-xs text-primary-500 font-bold">● NOW</span>
                                    @endif
                                </span>
                                <span class="text-xs text-gray-400 flex-shrink-0 ml-2">
                                    {{ \Carbon\Carbon::parse($block->start_time)->format('g:i') }}–{{ \Carbon\Carbon::parse($block->end_time)->format('g:i A') }}
                                </span>
                            </div>

                            @if($block->task || $block->project)
                                <p class="text-xs text-gray-400 truncate mt-0.5">
                                    {{ $block->task?->title ?? $block->project?->name }}
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
```

---

## 5. GoalProgressWidget

Horizontal progress bars for every active goal, color-coded by life area. Sorted by life area display order.

### Class

```php
// app/Filament/Widgets/GoalProgressWidget.php

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
```

### Blade View

```blade
{{-- resources/views/filament/widgets/goal-progress-widget.blade.php --}}

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">🎯 Active Goals</x-slot>
        <x-slot name="headerEnd">
            <a href="{{ route('filament.admin.resources.goals.index') }}"
               class="text-xs text-primary-500 hover:underline">All Goals →</a>
        </x-slot>

        @if($goals->isEmpty())
            <div class="text-center py-8 text-gray-400">
                <x-heroicon-o-flag class="w-8 h-8 mx-auto mb-2" />
                <p class="text-sm">No active goals yet.</p>
                <a href="{{ route('filament.admin.resources.goals.create') }}"
                   class="text-primary-500 text-sm hover:underline">Create your first goal →</a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($goals as $goal)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="text-base">{{ $goal->lifeArea?->icon }}</span>
                                <a href="{{ route('filament.admin.resources.goals.view', $goal) }}"
                                   class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate hover:text-primary-500">
                                    {{ $goal->title }}
                                </a>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0 ml-3">
                                <span class="text-xs text-gray-400">{{ $goal->horizon }}</span>
                                <span class="text-sm font-bold"
                                      style="color: {{ $goal->lifeArea?->color_hex ?? '#C9A84C' }}">
                                    {{ $goal->progress_percent }}%
                                </span>
                            </div>
                        </div>

                        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2">
                            <div
                                class="h-2 rounded-full transition-all duration-500"
                                style="
                                    width: {{ $goal->progress_percent }}%;
                                    background-color: {{ $goal->lifeArea?->color_hex ?? '#C9A84C' }};
                                "
                            ></div>
                        </div>

                        @if($goal->why)
                            <p class="text-xs text-gray-400 mt-1 italic truncate">
                                "{{ $goal->why }}"
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
```

---

## 6. HabitRingWidget

A circular progress ring showing today's overall habit completion percentage, plus a mini summary of streaks. Provides quick motivation at a glance.

### Class

```php
// app/Filament/Widgets/HabitRingWidget.php

namespace App\Filament\Widgets;

use App\Models\Habit;
use App\Models\HabitLog;

class HabitRingWidget extends BaseWidget
{
    protected static ?int $sort = 5;
    protected int | string | array $columnSpan = 1;
    protected static string $view = 'filament.widgets.habit-ring-widget';

    public function getViewData(): array
    {
        $habits    = Habit::where('status', 'active')->with('todayLog')->get();
        $total     = $habits->count();
        $completed = $habits->filter(fn ($h) => $h->todayLog?->status === 'completed')->count();
        $percent   = $total > 0 ? round(($completed / $total) * 100) : 0;

        // Circumference of the SVG ring: r=36 → C = 2π × 36 ≈ 226.2
        $circumference = 226.2;
        $dashOffset    = $circumference - ($circumference * $percent / 100);

        $topStreaks = Habit::where('status', 'active')
            ->where('streak_current', '>', 0)
            ->orderByDesc('streak_current')
            ->limit(3)
            ->get(['title', 'streak_current', 'life_area_id'])
            ->map(fn ($h) => [
                'title'  => $h->title,
                'streak' => $h->streak_current,
            ]);

        return compact('total', 'completed', 'percent', 'dashOffset', 'circumference', 'topStreaks');
    }
}
```

### Blade View

```blade
{{-- resources/views/filament/widgets/habit-ring-widget.blade.php --}}

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">🌿 Habits Today</x-slot>

        <div class="flex flex-col items-center py-2">
            {{-- SVG Ring --}}
            <div class="relative w-28 h-28 mb-4">
                <svg class="w-full h-full -rotate-90" viewBox="0 0 88 88">
                    {{-- Background track --}}
                    <circle cx="44" cy="44" r="36"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="8"
                        class="text-gray-100 dark:text-gray-800"
                    />
                    {{-- Progress arc --}}
                    <circle cx="44" cy="44" r="36"
                        fill="none"
                        stroke="#C9A84C"
                        stroke-width="8"
                        stroke-linecap="round"
                        stroke-dasharray="{{ $circumference }}"
                        stroke-dashoffset="{{ $dashOffset }}"
                        class="transition-all duration-700"
                    />
                </svg>
                {{-- Center text --}}
                <div class="absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-2xl font-bold text-gray-900 dark:text-white">
                        {{ $percent }}%
                    </span>
                    <span class="text-xs text-gray-400">
                        {{ $completed }}/{{ $total }}
                    </span>
                </div>
            </div>

            {{-- Encouragement message --}}
            <p class="text-sm text-center text-gray-500 mb-4">
                @if($percent === 100)
                    🎉 All habits done! Exceptional day.
                @elseif($percent >= 75)
                    💪 Strong progress — keep it going.
                @elseif($percent >= 50)
                    🌱 Halfway there. Finish strong.
                @elseif($percent > 0)
                    🔥 Good start. Don't stop now.
                @else
                    ✨ Today is waiting for you.
                @endif
            </p>

            {{-- Top streaks --}}
            @if($topStreaks->isNotEmpty())
                <div class="w-full border-t border-gray-100 dark:border-gray-800 pt-3 space-y-2">
                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-1">
                        🔥 Active Streaks
                    </p>
                    @foreach($topStreaks as $streak)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400 truncate">
                                {{ $streak['title'] }}
                            </span>
                            <span class="font-bold text-warning-500 ml-2 flex-shrink-0">
                                {{ $streak['streak'] }} days
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
```

---

## 7. AiIntentionWidget

Displays today's AI-generated morning intention. If one hasn't been generated yet, shows a button to generate it. Polls every 5 seconds while generation is in progress.

### Class

```php
// app/Filament/Widgets/AiIntentionWidget.php

namespace App\Filament\Widgets;

use App\Models\DailyPlan;
use App\Services\AiService;
use Filament\Notifications\Notification;

class AiIntentionWidget extends BaseWidget
{
    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 'full';
    protected static string $view = 'filament.widgets.ai-intention-widget';

    public bool $isGenerating = false;

    public function getViewData(): array
    {
        $plan = DailyPlan::todayOrCreate();

        return [
            'intention'    => $plan->ai_morning_prompt,
            'isGenerating' => $this->isGenerating,
            'hasPlan'      => (bool) $plan->id,
        ];
    }

    public function generate(): void
    {
        $this->isGenerating = true;

        try {
            $plan      = DailyPlan::todayOrCreate();
            $aiService = app(AiService::class);
            $intention = $aiService->generateMorningIntention($plan);

            $plan->update(['ai_morning_prompt' => $intention]);

            Notification::make()
                ->title('Morning intention generated')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Could not generate intention')
                ->body('Please try again in a moment.')
                ->danger()
                ->send();
        } finally {
            $this->isGenerating = false;
        }
    }
}
```

### Blade View

```blade
{{-- resources/views/filament/widgets/ai-intention-widget.blade.php --}}

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            🤖 AI Morning Intention
        </x-slot>

        <x-slot name="headerEnd">
            @if($intention)
                <button
                    wire:click="generate"
                    wire:loading.attr="disabled"
                    class="text-xs text-gray-400 hover:text-primary-500 transition-colors"
                >
                    ↻ Regenerate
                </button>
            @endif
        </x-slot>

        @if($isGenerating)
            <div class="flex items-center gap-3 py-4">
                <div class="w-5 h-5 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
                <span class="text-sm text-gray-500 italic">
                    Solas Rún is reading your goals and crafting today's intention...
                </span>
            </div>

        @elseif($intention)
            <blockquote class="border-l-4 border-warning-400 pl-4 py-1 my-2">
                <p class="text-base text-gray-700 dark:text-gray-300 italic leading-relaxed">
                    {{ $intention }}
                </p>
            </blockquote>

        @else
            <div class="text-center py-6">
                <p class="text-sm text-gray-400 mb-4">
                    Your AI morning intention hasn't been generated yet.<br>
                    It will draw from your active goals, recent reflections, and today's priorities.
                </p>
                <button
                    wire:click="generate"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg
                           bg-warning-500 hover:bg-warning-600 text-white text-sm font-medium
                           transition-colors disabled:opacity-50"
                >
                    <x-heroicon-o-sparkles class="w-4 h-4" />
                    <span wire:loading.remove>Generate Morning Intention</span>
                    <span wire:loading>Generating...</span>
                </button>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
```

---

## 8. StreakHighlightsWidget

Celebrates active habit streaks and milestone achievements. Provides positive reinforcement and a sense of momentum.

### Class

```php
// app/Filament/Widgets/StreakHighlightsWidget.php

namespace App\Filament\Widgets;

use App\Models\Habit;
use App\Models\Milestone;

class StreakHighlightsWidget extends BaseWidget
{
    protected static ?int $sort = 7;
    protected int | string | array $columnSpan = 1;
    protected static string $view = 'filament.widgets.streak-highlights-widget';

    public function getViewData(): array
    {
        $streaks = Habit::where('status', 'active')
            ->where('streak_current', '>=', 3)
            ->orderByDesc('streak_current')
            ->limit(5)
            ->with('lifeArea')
            ->get()
            ->map(fn ($habit) => [
                'title'   => $habit->title,
                'streak'  => $habit->streak_current,
                'best'    => $habit->streak_best,
                'color'   => $habit->lifeArea?->color_hex ?? '#C9A84C',
                'isPB'    => $habit->streak_current >= $habit->streak_best,
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
```

### Blade View

```blade
{{-- resources/views/filament/widgets/streak-highlights-widget.blade.php --}}

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">🏆 Highlights</x-slot>

        {{-- Streaks --}}
        @if($streaks->isNotEmpty())
            <div class="space-y-2 mb-4">
                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">
                    Habit Streaks
                </p>
                @foreach($streaks as $streak)
                    <div class="flex items-center gap-3 p-2 rounded-lg bg-gray-50 dark:bg-gray-900">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center font-bold text-white text-sm"
                             style="background-color: {{ $streak['color'] }}">
                            {{ $streak['streak'] }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
                                {{ $streak['title'] }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ $streak['streak'] }} day streak
                                @if($streak['isPB'])
                                    · <span class="text-warning-500">Personal best! 🌟</span>
                                @else
                                    · Best: {{ $streak['best'] }}
                                @endif
                            </p>
                        </div>
                        <span class="text-xl">🔥</span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Recent Milestones --}}
        @if($recentMilestones->isNotEmpty())
            <div class="space-y-2">
                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide">
                    Recent Milestones
                </p>
                @foreach($recentMilestones as $milestone)
                    <div class="flex items-start gap-2 p-2 rounded-lg bg-success-50 dark:bg-success-950">
                        <span class="text-success-500 flex-shrink-0 mt-0.5">✅</span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">
                                {{ $milestone->title }}
                            </p>
                            <p class="text-xs text-gray-400 truncate">
                                {{ $milestone->goal?->title }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @if($streaks->isEmpty() && $recentMilestones->isEmpty())
            <div class="text-center py-6 text-gray-400">
                <p class="text-sm">Keep going — your streaks and wins will appear here.</p>
            </div>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>
```

---

## 9. DoneDeliveredWidget

Showcases recent completed work and the value delivered this month. Displays outcome metrics, client quotes, and links to the full Done & Delivered resource.

### Class

```php
// app/Filament/Widgets/DoneDeliveredWidget.php

namespace App\Filament\Widgets;

class DoneDeliveredWidget extends BaseWidget
{
    protected static ?int $sort = 8;
    protected int | string | array $columnSpan = 1;
    protected static string $view = 'filament.widgets.done-delivered-widget';

    public function getViewData(): array
    {
        $recentDoneItems = \App\Models\MeetingDoneItem::with('meeting')
            ->latest()
            ->limit(5)
            ->get();

        $totalValueDelivered = \App\Models\MeetingDoneItem::sum('value_delivered');
        $thisMonthDone       = \App\Models\Task::where('status', 'done')
            ->whereMonth('updated_at', now()->month)
            ->count();

        return compact('recentDoneItems', 'totalValueDelivered', 'thisMonthDone');
    }
}
```

### Blade View

```blade
{{-- resources/views/filament/widgets/done-delivered-widget.blade.php --}}

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">✅ Done & Delivered</x-slot>
        <x-slot name="headerEnd">
            <a href="{{ route('filament.admin.resources.meeting-done-items.index') }}"
               class="text-xs text-primary-500 hover:underline">All Outcomes →</a>
        </x-slot>

        {{-- Value Delivered & This Month Count --}}
        <div class="grid grid-cols-2 gap-3 mb-4">
            <div class="text-center p-3 rounded-lg bg-success-50 dark:bg-success-950">
                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Value Delivered</p>
                <p class="text-lg font-bold text-success-600 dark:text-success-400">
                    ${{ number_format($totalValueDelivered ?? 0) }}
                </p>
            </div>
            <div class="text-center p-3 rounded-lg bg-warning-50 dark:bg-warning-950">
                <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Done This Month</p>
                <p class="text-lg font-bold text-warning-600 dark:text-warning-400">
                    {{ $thisMonthDone ?? 0 }}
                </p>
            </div>
        </div>

        {{-- Recent Done Items --}}
        @if($recentDoneItems->isNotEmpty())
            <div class="space-y-2">
                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-2">
                    Last 5 Outcomes
                </p>
                @foreach($recentDoneItems as $item)
                    <div class="flex items-start gap-2 p-2 rounded-lg bg-gray-50 dark:bg-gray-900 text-sm">
                        <span class="text-success-500 flex-shrink-0">✓</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-800 dark:text-gray-200 truncate">
                                {{ $item->outcome ?? 'Outcome' }}
                            </p>
                            @if($item->client_quote)
                                <p class="text-xs text-gray-400 italic mt-0.5 truncate">
                                    "{{ $item->client_quote }}"
                                </p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-4 text-gray-400">
                <p class="text-sm">No completed outcomes yet this month.</p>
                <a href="{{ route('filament.admin.resources.meeting-done-items.create') }}"
                   class="text-primary-500 text-sm hover:underline mt-1 inline-block">
                    Log your first outcome →
                </a>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
```

---

## 10. Dashboard Layout & Registration

### Register Widgets in the Panel

```php
// app/Providers/Filament/AdminPanelProvider.php

->widgets([
    \App\Filament\Widgets\DayThemeWidget::class,
    \App\Filament\Widgets\MorningChecklistWidget::class,
    \App\Filament\Widgets\TimeBlockTimelineWidget::class,
    \App\Filament\Widgets\GoalProgressWidget::class,
    \App\Filament\Widgets\HabitRingWidget::class,
    \App\Filament\Widgets\AiIntentionWidget::class,
    \App\Filament\Widgets\StreakHighlightsWidget::class,
    \App\Filament\Widgets\OpportunityPipelineWidget::class,  // ← NEW (from deferral doc)
    \App\Filament\Widgets\DoneDeliveredWidget::class,         // ← NEW (see section 9)
])
```

### Dashboard Page — Custom Layout

Override the default dashboard to control column layout precisely:

```bash
php artisan make:filament-page Dashboard
```

```php
// app/Filament/Pages/Dashboard.php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Daily Command Center';
    protected static ?int $navigationSort = -1; // Always first

    public function getColumns(): int | string | array
    {
        return [
            'default' => 1,
            'sm'      => 2,
            'md'      => 3,
            'xl'      => 3,
        ];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\DayThemeWidget::class,        // Full width  — row 1
            \App\Filament\Widgets\AiIntentionWidget::class,     // Full width  — row 2
            \App\Filament\Widgets\MorningChecklistWidget::class,// 2 cols      — row 3 left
            \App\Filament\Widgets\TimeBlockTimelineWidget::class,// 1 col       — row 3 right
            \App\Filament\Widgets\GoalProgressWidget::class,    // 2 cols      — row 4 left
            \App\Filament\Widgets\HabitRingWidget::class,       // 1 col       — row 4 right
            \App\Filament\Widgets\StreakHighlightsWidget::class, // 1 col       — row 4 far right
            \App\Filament\Widgets\OpportunityPipelineWidget::class, // 1 col  — row 5 left
            \App\Filament\Widgets\DoneDeliveredWidget::class,        // 1 col  — row 5 right
        ];
    }
}
```

### Dashboard Layout Diagram

```
┌─────────────────────────────────────────────────────────┐
│                   DayThemeWidget                        │
└─────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────┐
│                  AiIntentionWidget                      │
└─────────────────────────────────────────────────────────┘
┌───────────────────────────────────┬─────────────────────┐
│     MorningChecklistWidget        │ TimeBlockTimeline   │
└───────────────────────────────────┴─────────────────────┘
┌───────────────────────────────────┬──────────┬──────────┐
│      GoalProgressWidget           │  Habit   │ Streak   │
│                                   │   Ring   │Highlights│
└───────────────────────────────────┴──────────┴──────────┘
┌──────────────────────┬──────────────────────────────────┐
│  OpportunityPipeline │      DoneDeliveredWidget         │
│      Widget          │   (recent outcomes & value)      │
└──────────────────────┴───────────────────────────────────┘
```

---

## 11. Shared Widget Styles

### Tailwind CSS Custom Colors

Add Solas Rún's brand palette to `tailwind.config.js` so the custom hex values used in widgets resolve correctly:

```js
// tailwind.config.js

import preset from './vendor/filament/support/tailwind.config.preset'

export default {
    presets: [preset],
    content: [
        './app/Filament/**/*.php',
        './resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],
    theme: {
        extend: {
            colors: {
                'solas-gold':  '#C9A84C',
                'solas-green': '#1A3C2E',
                'solas-ember': '#B94A2C',
            },
        },
    },
}
```

### Widget Refresh

To keep the dashboard live without a full page reload, widgets that show real-time data (habit ring, checklist) can poll for updates:

```php
// Add to any widget class that should auto-refresh

protected static ?string $pollingInterval = '30s'; // Refresh every 30 seconds
```

Set to `null` to disable polling and rely on Livewire events only.

### Livewire Event — Cross-Widget Updates

When a habit is logged in `MorningChecklistWidget`, broadcast a Livewire event so `HabitRingWidget` and `StreakHighlightsWidget` update simultaneously:

```php
// In MorningChecklistWidget::toggleHabit()

$this->dispatch('habit-logged');

// In HabitRingWidget and StreakHighlightsWidget

#[On('habit-logged')]
public function refresh(): void
{
    // Livewire re-renders the component automatically
}
```

---

*Solas Rún • Version 1.1 • Dashboard Widgets*
