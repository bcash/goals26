<x-filament-widgets::widget>
    <x-filament::section heading="Email Performance" icon="heroicon-o-chart-bar">
        <div class="grid grid-cols-3 gap-4">
            <div class="text-center">
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $averageQuality ?? '--' }}
                </p>
                <p class="text-xs text-gray-500 mt-1">Avg Quality Score</p>
                <p class="text-xs text-gray-400">Last 7 days</p>
            </div>

            <div class="text-center">
                <p class="text-2xl font-bold {{ $needsReviewCount > 0 ? 'text-warning-600' : 'text-gray-900 dark:text-gray-100' }}">
                    {{ $needsReviewCount }}
                </p>
                <p class="text-xs text-gray-500 mt-1">Needs Review</p>
                <p class="text-xs text-gray-400">Open items</p>
            </div>

            <div class="text-center">
                <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    {{ $weeklyConversations }}
                </p>
                <p class="text-xs text-gray-500 mt-1">Conversations</p>
                <p class="text-xs text-gray-400">This week</p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
