<x-filament-panels::page>
    <div class="space-y-6">
        @if(! $connected)
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-xl p-6 text-center">
                <x-heroicon-o-exclamation-triangle class="w-12 h-12 mx-auto mb-3 text-yellow-500" />
                <p class="text-lg font-medium text-yellow-800 dark:text-yellow-200 mb-2">FreeScout not connected</p>
                <p class="text-sm text-yellow-600 dark:text-yellow-400 mb-4">Configure your FreeScout URL and API key in the <code>.env</code> file to enable email integration.</p>
                <div class="text-xs text-yellow-600 dark:text-yellow-400 font-mono bg-yellow-100 dark:bg-yellow-900/40 rounded-lg p-3 inline-block text-left">
                    FREESCOUT_URL=https://your-freescout.example.com<br>
                    FREESCOUT_API_KEY=your-api-key
                </div>
            </div>
        @else
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-xl p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-check-circle class="w-6 h-6 text-green-500" />
                    <span class="text-green-800 dark:text-green-200 font-medium">FreeScout connected</span>
                </div>
                <div class="flex gap-2">
                    {{ $this->syncNowAction }}
                    {{ $this->syncMailboxesAction }}
                </div>
            </div>

            @if(count($mailboxes) > 0)
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Mailboxes</h3>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($mailboxes as $mailbox)
                            <div class="px-4 py-3 flex items-center justify-between">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-gray-100">{{ $mailbox['name'] }}</p>
                                    <p class="text-sm text-gray-500">{{ $mailbox['email'] }}</p>
                                    @if($mailbox['last_synced_at'])
                                        <p class="text-xs text-gray-400 mt-1">Last synced {{ $mailbox['last_synced_at'] }}</p>
                                    @endif
                                </div>
                                <div class="flex items-center gap-4">
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox"
                                               wire:click="toggleSync({{ $mailbox['id'] }})"
                                               @checked($mailbox['sync_enabled'])
                                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                        Sync Enabled
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-center py-8 text-gray-400">
                    <p>No mailboxes found. Click "Sync Mailboxes" to fetch your FreeScout mailboxes.</p>
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>
