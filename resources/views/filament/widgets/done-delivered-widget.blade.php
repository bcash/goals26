<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Done & Delivered</x-slot>
        <x-slot name="headerEnd">
            <a href="{{ route('filament.admin.resources.meeting-done-items.index') }}"
               class="text-xs text-primary-500 hover:underline">All Outcomes</a>
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
                        <span class="text-success-500 flex-shrink-0">
                            <x-heroicon-o-check class="w-4 h-4" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium text-gray-800 dark:text-gray-200 truncate">
                                {{ $item->title ?? $item->outcome ?? 'Outcome' }}
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
                    Log your first outcome
                </a>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
