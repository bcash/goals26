<x-filament-widgets::widget>
    <x-filament::section heading="Upcoming Events" icon="heroicon-o-calendar-days">
        @if(empty($eventGroups))
            <p class="text-sm text-gray-400 text-center py-4">No upcoming events in the next 3 days.</p>
        @else
            <div class="space-y-4">
                @foreach($eventGroups as $group)
                    <div>
                        <h4 class="text-xs font-semibold uppercase tracking-wider {{ $group['is_today'] ? 'text-primary-600' : 'text-gray-500' }} mb-2">
                            {{ $group['is_today'] ? 'Today' : $group['date'] }}
                        </h4>
                        <div class="space-y-2">
                            @foreach($group['events'] as $event)
                                <div class="flex items-center justify-between rounded-lg bg-gray-50 dark:bg-gray-800 px-3 py-2">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $event['title'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $event['time'] }}</p>
                                    </div>
                                    <div class="flex items-center gap-2 ml-3">
                                        @if($event['attendee_count'] > 0)
                                            <span class="text-xs text-gray-400">
                                                <x-heroicon-m-users class="w-3 h-3 inline" /> {{ $event['attendee_count'] }}
                                            </span>
                                        @endif
                                        @if($event['has_agenda'])
                                            <span class="text-xs text-green-500" title="Has agenda">
                                                <x-heroicon-m-clipboard-document-list class="w-3 h-3" />
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
