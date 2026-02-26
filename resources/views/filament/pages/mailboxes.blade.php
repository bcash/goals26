<x-filament-panels::page>
    @if(empty($mailboxes))
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
            <x-heroicon-o-inbox-stack class="w-12 h-12 mx-auto mb-3 text-gray-400" />
            <p class="text-lg font-medium text-gray-600 dark:text-gray-300 mb-2">No mailboxes configured</p>
            <p class="text-sm text-gray-400 mb-4">Connect your FreeScout instance and enable mailbox syncing to see your mailboxes here.</p>
            <a href="{{ \App\Filament\Pages\FreeScoutSettings::getUrl() }}"
               class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 transition-colors">
                <x-heroicon-o-cog-6-tooth class="w-4 h-4 mr-2" />
                FreeScout Settings
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            @foreach($mailboxes as $mailbox)
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    {{-- Mailbox Header --}}
                    <div class="p-4 border-b border-gray-200 dark:border-gray-700">
                        <a href="{{ $this->getMailboxListUrl($mailbox['freescout_mailbox_id']) }}"
                           class="text-sm font-bold text-gray-900 dark:text-gray-100 hover:text-primary-600 dark:hover:text-primary-400 transition-colors">
                            {{ $mailbox['name'] }}
                        </a>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $mailbox['email'] }}</p>
                    </div>

                    {{-- Folder Rows --}}
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        {{-- Unassigned --}}
                        <div class="px-4 py-2.5 flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Unassigned</span>
                            @if($mailbox['folders']['unassigned'] > 0)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300">
                                    {{ $mailbox['folders']['unassigned'] }}
                                </span>
                            @else
                                <span class="text-xs text-gray-400">0</span>
                            @endif
                        </div>

                        {{-- Mine --}}
                        <div class="px-4 py-2.5 flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Mine</span>
                            <div class="flex items-center gap-2">
                                @if($mailbox['mine_latest'])
                                    <span class="text-xs text-gray-400">{{ $mailbox['mine_latest'] }}</span>
                                @endif
                                <span class="text-xs font-medium text-gray-900 dark:text-gray-100">
                                    {{ $mailbox['folders']['mine'] }}
                                </span>
                            </div>
                        </div>

                        {{-- Assigned --}}
                        <div class="px-4 py-2.5 flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Assigned</span>
                            <span class="text-xs text-gray-900 dark:text-gray-100">{{ $mailbox['folders']['assigned'] }}</span>
                        </div>

                        {{-- Closed --}}
                        <div class="px-4 py-2.5 flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Closed</span>
                            <span class="text-xs text-gray-400">{{ $mailbox['folders']['closed'] }}</span>
                        </div>

                        {{-- Spam --}}
                        <div class="px-4 py-2.5 flex items-center justify-between">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Spam</span>
                            <span class="text-xs text-gray-400">{{ $mailbox['folders']['spam'] }}</span>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
                        <p class="text-xs text-gray-400">
                            {{ $mailbox['total'] }} total
                            @if($mailbox['last_synced_at'])
                                &middot; synced {{ $mailbox['last_synced_at'] }}
                            @endif
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
