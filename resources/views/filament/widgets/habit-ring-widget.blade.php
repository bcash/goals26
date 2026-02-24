<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Habits Today</x-slot>

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
                    All habits done! Exceptional day.
                @elseif($percent >= 75)
                    Strong progress -- keep it going.
                @elseif($percent >= 50)
                    Halfway there. Finish strong.
                @elseif($percent > 0)
                    Good start. Don't stop now.
                @else
                    Today is waiting for you.
                @endif
            </p>

            {{-- Top streaks --}}
            @if($topStreaks->isNotEmpty())
                <div class="w-full border-t border-gray-100 dark:border-gray-800 pt-3 space-y-2">
                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-1">
                        Active Streaks
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
