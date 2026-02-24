<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Highlights</x-slot>

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
                                    &middot; <span class="text-warning-500">Personal best!</span>
                                @else
                                    &middot; Best: {{ $streak['best'] }}
                                @endif
                            </p>
                        </div>
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
                        <span class="text-success-500 flex-shrink-0 mt-0.5">
                            <x-heroicon-o-check-circle class="w-4 h-4" />
                        </span>
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
                <p class="text-sm">Keep going -- your streaks and wins will appear here.</p>
            </div>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>
