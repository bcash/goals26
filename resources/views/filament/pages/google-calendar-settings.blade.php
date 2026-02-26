<x-filament-panels::page>
    <div class="space-y-6">
        @if(! $isConnected)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl p-6 text-center">
                <x-heroicon-o-exclamation-triangle class="w-12 h-12 mx-auto mb-3 text-yellow-500" />
                <p class="text-lg font-medium text-yellow-800 dark:text-yellow-200 mb-2">Google Calendar not connected</p>
                <p class="text-sm text-yellow-600 dark:text-yellow-400 mb-4">Connect your Google account to sync calendar events.</p>
                {{ $this->connectAction }}
            </div>
        @else
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-xl p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-check-circle class="w-6 h-6 text-green-500" />
                    <span class="text-green-800 dark:text-green-200 font-medium">Google Calendar connected</span>
                </div>
                <div class="flex gap-2">
                    {{ $this->syncNowAction }}
                    {{ $this->refreshCalendarsAction }}
                    {{ $this->disconnectAction }}
                </div>
            </div>

            @if(count($calendars) > 0)
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Your Calendars</h3>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($calendars as $cal)
                            <div class="px-4 py-3 flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $cal['calendar_name'] }}</p>
                                    <p class="text-sm text-gray-500">{{ $cal['google_calendar_id'] }}</p>
                                </div>
                                <div class="flex items-center gap-4">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox"
                                               wire:click="toggleSync({{ $cal['id'] }})"
                                               @checked($cal['sync_enabled'])
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        Sync
                                    </label>
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox"
                                               wire:click="toggleAttendeesOnly({{ $cal['id'] }})"
                                               @checked($cal['only_with_attendees'])
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        Only with attendees
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-center py-8 text-gray-400">
                    <p>No calendars found. Click "Refresh Calendar List" to fetch your Google calendars.</p>
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>
