<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Today's Schedule</x-slot>
        <x-slot name="headerEnd">
            <a href="{{ $editPlanUrl }}" class="text-xs text-primary-500 hover:underline">
                Edit Plan
            </a>
        </x-slot>

        @if($blocks->isEmpty())
            <div class="text-center py-8 text-gray-400">
                <x-heroicon-o-clock class="w-8 h-8 mx-auto mb-2" />
                <p class="text-sm">No time blocks scheduled.</p>
                <a href="{{ $editPlanUrl }}" class="text-primary-500 text-sm hover:underline">
                    Build your schedule
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
                                        <span class="ml-1 text-xs text-primary-500 font-bold">NOW</span>
                                    @endif
                                </span>
                                <span class="text-xs text-gray-400 flex-shrink-0 ml-2">
                                    {{ \Carbon\Carbon::parse($block->start_time)->format('g:i') }}&ndash;{{ \Carbon\Carbon::parse($block->end_time)->format('g:i A') }}
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
