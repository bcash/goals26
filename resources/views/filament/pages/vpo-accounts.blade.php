<x-filament-panels::page>
    {{-- Search --}}
    <div class="mb-6">
        <div class="w-full max-w-md">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search Accounts</label>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name or email..."
                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            />
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Account List --}}
        <div class="lg:col-span-1">
            @if(empty($accounts))
                <div class="text-center py-12 text-gray-400">
                    <x-heroicon-o-building-office class="w-12 h-12 mx-auto mb-3" />
                    <p class="text-lg font-medium mb-1">No accounts found</p>
                    <p class="text-sm">Try adjusting your search query.</p>
                </div>
            @else
                <div class="space-y-2">
                    @foreach($accounts as $account)
                        <button
                            wire:click="viewAccount('{{ $account['id'] ?? '' }}')"
                            class="w-full text-left p-3 rounded-lg border transition-colors
                                {{ ($selectedAccount['id'] ?? '') == ($account['id'] ?? '')
                                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-950'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 bg-white dark:bg-gray-900'
                                }}"
                        >
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $account['name'] ?? 'Unknown' }}
                            </p>
                            @if(! empty($account['industry']['name']) && $account['industry']['name'] !== 'Other')
                                <span class="inline-flex items-center px-2 py-0.5 mt-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                    {{ $account['industry']['name'] }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Account Detail Panel --}}
        <div class="lg:col-span-2">
            @if($selectedAccount)
                <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                    {{-- Header --}}
                    <div class="flex items-start justify-between mb-6">
                        <div class="flex items-start gap-4">
                            @if(! empty($selectedAccount['avatar_url']))
                                <img
                                    src="{{ $selectedAccount['avatar_url'] }}"
                                    alt="{{ $selectedAccount['name'] }}"
                                    class="w-12 h-12 rounded-lg object-cover flex-shrink-0"
                                />
                            @endif
                            <div>
                                <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">
                                    {{ $selectedAccount['name'] ?? 'Unknown Account' }}
                                </h3>
                                @if(! empty($selectedAccount['contact_name']))
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $selectedAccount['contact_name'] }}</p>
                                @endif
                                @if(! empty($selectedAccount['contact_email']))
                                    <p class="text-sm text-gray-400">{{ $selectedAccount['contact_email'] }}</p>
                                @endif
                                @if(! empty($selectedAccount['contact_phone']))
                                    <p class="text-sm text-gray-400">{{ $selectedAccount['contact_phone'] }}</p>
                                @endif
                                <div class="flex items-center gap-2 mt-1">
                                    <p class="text-xs text-gray-400 font-mono">ID: {{ $selectedAccount['id'] }}</p>
                                    @if(! empty($selectedAccount['industry']['name']))
                                        <span class="text-xs text-gray-400">&middot; {{ $selectedAccount['industry']['name'] }}</span>
                                    @endif
                                    @if(! empty($selectedAccount['payment_type']))
                                        <span class="text-xs text-gray-400">&middot; {{ ucfirst($selectedAccount['payment_type']) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <button wire:click="clearSelection" class="text-gray-400 hover:text-gray-600">
                            <x-heroicon-o-x-mark class="w-5 h-5" />
                        </button>
                    </div>

                    {{-- Websites (from account detail) --}}
                    @if(! empty($selectedAccount['websites']))
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-3">
                                Websites ({{ count($selectedAccount['websites']) }})
                            </h4>
                            <div class="space-y-2">
                                @foreach($selectedAccount['websites'] as $website)
                                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                                        <div>
                                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ $website['name'] ?? 'Unknown' }}
                                            </p>
                                            @if(! empty($website['public_url']))
                                                <a href="{{ $website['public_url'] }}" target="_blank" class="text-xs text-primary-500 hover:underline">
                                                    {{ $website['public_url'] }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Domains (from account detail) --}}
                    @if(! empty($selectedAccount['domains']))
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-3">
                                Domains ({{ count($selectedAccount['domains']) }})
                            </h4>
                            <div class="space-y-2">
                                @foreach($selectedAccount['domains'] as $domain)
                                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            {{ $domain['name'] ?? 'Unknown' }}
                                        </p>
                                        @if(! empty($domain['registration_expires']))
                                            <span class="text-xs text-gray-400">
                                                Expires {{ \Carbon\Carbon::parse($domain['registration_expires'])->format('M j, Y') }}
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Tasks --}}
                    @if(! empty($tasks))
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-3">
                                Tasks ({{ count($tasks) }})
                            </h4>
                            <div class="space-y-2">
                                @foreach($tasks as $task)
                                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                                        <p class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ $task['name'] ?? 'Unknown' }}
                                        </p>
                                        @if(isset($task['status']))
                                            <span class="text-xs text-gray-400 capitalize">{{ $task['status'] }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Invoices --}}
                    @if(! empty($invoices))
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-3">
                                Invoices ({{ count($invoices) }})
                            </h4>
                            <div class="space-y-2">
                                @foreach($invoices as $invoice)
                                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                                        <div>
                                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                                {{ $invoice['number'] ?? $invoice['id'] ?? 'Unknown' }}
                                            </p>
                                        </div>
                                        <div class="flex items-center gap-3">
                                            @if(isset($invoice['total_cents']))
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    ${{ number_format($invoice['total_cents'] / 100, 2) }}
                                                </span>
                                            @endif
                                            @if(isset($invoice['status']))
                                                <span class="text-xs text-gray-400 capitalize">{{ $invoice['status'] }}</span>
                                            @endif
                                            @if(isset($invoice['source']))
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                                    {{ ucfirst($invoice['source']) }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Sub-accounts --}}
                    @if(! empty($selectedAccount['children']))
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-3">
                                Sub-accounts ({{ count($selectedAccount['children']) }})
                            </h4>
                            <div class="space-y-2">
                                @foreach($selectedAccount['children'] as $child)
                                    <button
                                        wire:click="viewAccount('{{ $child['id'] }}')"
                                        class="w-full text-left py-2 border-b border-gray-100 dark:border-gray-800 last:border-0 hover:text-primary-500"
                                    >
                                        <p class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ $child['name'] ?? 'Unknown' }}
                                        </p>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Empty state when no related data --}}
                    @if(empty($selectedAccount['websites']) && empty($selectedAccount['domains']) && empty($tasks) && empty($invoices) && empty($selectedAccount['children']))
                        <p class="text-sm text-gray-400 text-center py-4">
                            No related data available for this account.
                        </p>
                    @endif
                </div>
            @else
                <div class="text-center py-16 text-gray-400">
                    <x-heroicon-o-cursor-arrow-rays class="w-12 h-12 mx-auto mb-3" />
                    <p class="text-lg font-medium mb-1">Select an account</p>
                    <p class="text-sm">Click an account from the list to view details.</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
