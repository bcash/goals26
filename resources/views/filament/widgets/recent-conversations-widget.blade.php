<x-filament-widgets::widget>
    <x-filament::section heading="Recent Conversations" icon="heroicon-o-envelope">
        @if(empty($conversations))
            <p class="text-sm text-gray-400 text-center py-4">No conversations needing attention.</p>
        @else
            <div class="space-y-2">
                @foreach($conversations as $conversation)
                    <a href="{{ $conversation['url'] }}" class="block rounded-lg bg-gray-50 dark:bg-gray-800 px-3 py-2 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate">{{ $conversation['subject'] }}</p>
                                <p class="text-xs text-gray-500">{{ $conversation['contact_name'] }}</p>
                            </div>
                            <div class="flex items-center gap-2 ml-3">
                                <span @class([
                                    'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                                    'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-500/10 dark:text-green-400 dark:ring-green-500/20' => $conversation['status'] === 'active',
                                    'bg-yellow-50 text-yellow-700 ring-yellow-600/20 dark:bg-yellow-500/10 dark:text-yellow-400 dark:ring-yellow-500/20' => $conversation['status'] === 'pending',
                                    'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-500/10 dark:text-gray-400 dark:ring-gray-500/20' => ! in_array($conversation['status'], ['active', 'pending']),
                                ])>
                                    {{ ucfirst($conversation['status']) }}
                                </span>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">{{ $conversation['last_message_at'] }}</p>
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
