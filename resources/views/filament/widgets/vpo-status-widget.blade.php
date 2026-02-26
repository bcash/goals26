<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">VPO Accounts</x-slot>
        <x-slot name="headerEnd">
            @if($connected)
                <span class="inline-flex items-center gap-1.5 text-xs text-success-600">
                    <span class="w-2 h-2 rounded-full bg-success-500"></span>
                    Connected
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 text-xs text-danger-600">
                    <span class="w-2 h-2 rounded-full bg-danger-500"></span>
                    Unavailable
                </span>
            @endif
        </x-slot>

        @if($connected && $accountCount > 0)
            <div class="space-y-2">
                @foreach($accounts as $account)
                    <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-800 last:border-0">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">
                                {{ $account['name'] ?? 'Unknown' }}
                            </p>
                        </div>
                        @if(! empty($account['industry']['name']) && $account['industry']['name'] !== 'Other')
                            <span class="text-xs text-gray-400 flex-shrink-0 ml-2">
                                {{ $account['industry']['name'] }}
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800">
                <a href="{{ route('filament.admin.pages.vpo-accounts') }}"
                   class="text-xs text-primary-500 hover:underline">
                    View All Accounts →
                </a>
            </div>
        @elseif($connected)
            <p class="text-sm text-gray-400 py-4 text-center">No accounts found</p>
        @else
            <div class="text-center py-4">
                <x-heroicon-o-exclamation-triangle class="w-8 h-8 mx-auto mb-2 text-warning-400" />
                <p class="text-sm text-gray-400">VPO server is not responding</p>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
