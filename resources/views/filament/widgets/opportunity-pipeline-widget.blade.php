<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Opportunity Pipeline</x-slot>
        <x-slot name="headerEnd">
            <a href="{{ route('filament.admin.resources.opportunity-pipelines.index') }}"
               class="text-xs text-primary-500 hover:underline">View All</a>
        </x-slot>

        {{-- Weighted pipeline value --}}
        <div class="text-center py-3 mb-4 bg-success-50 dark:bg-success-950 rounded-lg">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Weighted Pipeline</p>
            <p class="text-2xl font-bold text-success-600">
                ${{ number_format($weightedValue, 0) }}
            </p>
            <p class="text-xs text-gray-400">
                of ${{ number_format($totalValue, 0) }} total identified
            </p>
        </div>

        {{-- Stage breakdown --}}
        @if($byStage->isNotEmpty())
            <div class="mb-4 space-y-1">
                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-2">
                    By Stage
                </p>
                @foreach($byStage as $stage => $data)
                    <div class="flex items-center justify-between text-sm py-1">
                        <span class="text-gray-600 dark:text-gray-400 capitalize">
                            {{ str_replace('-', ' ', $stage) }}
                        </span>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-400">{{ $data['count'] }} items</span>
                            <span class="font-medium text-gray-700 dark:text-gray-300">
                                ${{ number_format($data['weighted'], 0) }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Actions due this week --}}
        @if($actionsThisWeek->isNotEmpty())
            <div class="mb-4">
                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-2">
                    Actions Due This Week
                </p>
                @foreach($actionsThisWeek->take(3) as $opp)
                    <div class="flex items-start gap-2 py-1.5 border-b border-gray-100 dark:border-gray-800 last:border-0">
                        <span class="text-warning-500 flex-shrink-0 mt-0.5">
                            <x-heroicon-o-arrow-right class="w-3 h-3" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">
                                {{ $opp->client_name }}
                            </p>
                            <p class="text-xs text-gray-400 truncate">{{ $opp->next_action }}</p>
                        </div>
                        <span class="text-xs text-gray-400 flex-shrink-0 ml-auto">
                            {{ $opp->next_action_date?->format('M j') }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Alerts --}}
        @if($overdueItems > 0)
            <a href="{{ \App\Filament\Resources\DeferredItemResource::getUrl('index') }}"
               class="flex items-center gap-2 p-2 rounded-lg bg-warning-50 dark:bg-warning-950 mb-2 hover:bg-warning-100 transition-colors">
                <span class="text-warning-500">
                    <x-heroicon-o-exclamation-triangle class="w-4 h-4" />
                </span>
                <span class="text-sm text-warning-700 dark:text-warning-400">
                    {{ $overdueItems }} items overdue for review
                </span>
            </a>
        @endif

        @if($staleHighValue > 0)
            <a href="{{ \App\Filament\Resources\DeferredItemResource::getUrl('index') }}"
               class="flex items-center gap-2 p-2 rounded-lg bg-info-50 dark:bg-info-950 hover:bg-info-100 transition-colors">
                <span class="text-info-500">
                    <x-heroicon-o-light-bulb class="w-4 h-4" />
                </span>
                <span class="text-sm text-info-700 dark:text-info-400">
                    {{ $staleHighValue }} high-value opportunities need attention
                </span>
            </a>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>
