<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Morning Checklist
            @if($morningDone)
                <span class="text-sm font-normal text-success-500 ml-2">Morning session complete</span>
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
                    <a href="{{ route('filament.admin.resources.daily-plans.edit', \App\Models\DailyPlan::todayOrCreate()) }}"
                       class="text-primary-500 text-sm hover:underline">Set your Top 3</a>
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
                            'morning'   => 'AM',
                            'afternoon' => 'PM',
                            'evening'   => 'Eve',
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
