<x-filament-panels::page>
    {{-- Search --}}
    <div class="mb-6">
        <div class="w-full max-w-md">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search Accounts</label>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name, email, or ID..."
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
                                {{ ($selectedAccount['id'] ?? '') === ($account['id'] ?? '')
                                    ? 'border-primary-500 bg-primary-50 dark:bg-primary-950'
                                    : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 bg-white dark:bg-gray-900'
                                }}"
                        >
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                {{ $account['name'] ?? 'Unknown' }}
                            </p>
                            @if(isset($account['status']))
                                <span class="inline-flex items-center px-2 py-0.5 mt-1 rounded-full text-xs font-medium
                                    {{ ($account['status'] ?? '') === 'active'
                                        ? 'bg-success-100 text-success-700 dark:bg-success-900 dark:text-success-300'
                                        : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                                    }}">
                                    {{ ucfirst($account['status'] ?? 'unknown') }}
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
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-200">
                                {{ $selectedAccount['name'] ?? 'Unknown Account' }}
                            </h3>
                            @if(isset($selectedAccount['email']))
                                <p class="text-sm text-gray-400">{{ $selectedAccount['email'] }}</p>
                            @endif
                            @if(isset($selectedAccount['id']))
                                <p class="text-xs text-gray-400 mt-1 font-mono">ID: {{ $selectedAccount['id'] }}</p>
                            @endif
                        </div>
                        <button wire:click="clearSelection" class="text-gray-400 hover:text-gray-600">
                            <x-heroicon-o-x-mark class="w-5 h-5" />
                        </button>
                    </div>

                    {{-- Contacts --}}
                    @if(!empty($contacts))
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-3">
                                Contacts ({{ count($contacts) }})
                            </h4>
                            <div class="space-y-2">
                                @foreach($contacts as $contact)
                                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                                        <div>
                                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                {{ $contact['name'] ?? 'Unknown' }}
                                            </p>
                                            <p class="text-xs text-gray-400">{{ $contact['email'] ?? '' }}</p>
                                        </div>
                                        @if(isset($contact['role']))
                                            <span class="text-xs text-gray-400">{{ $contact['role'] }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Projects --}}
                    @if(!empty($projects))
                        <div class="mb-6">
                            <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-3">
                                Projects ({{ count($projects) }})
                            </h4>
                            <div class="space-y-2">
                                @foreach($projects as $project)
                                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                                        <p class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ $project['name'] ?? 'Unknown' }}
                                        </p>
                                        @if(isset($project['status']))
                                            <span class="text-xs text-gray-400 capitalize">{{ $project['status'] }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Invoices --}}
                    @if(!empty($invoices))
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
                                            @if(isset($invoice['amount']))
                                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                    ${{ number_format($invoice['amount'], 2) }}
                                                </span>
                                            @endif
                                            @if(isset($invoice['status']))
                                                <span class="text-xs text-gray-400 capitalize">{{ $invoice['status'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Tickets --}}
                    @if(!empty($tickets))
                        <div>
                            <h4 class="text-sm font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wide mb-3">
                                Tickets ({{ count($tickets) }})
                            </h4>
                            <div class="space-y-2">
                                @foreach($tickets as $ticket)
                                    <div class="flex items-center justify-between py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                                        <p class="text-sm text-gray-700 dark:text-gray-300">
                                            {{ $ticket['subject'] ?? $ticket['title'] ?? 'Unknown' }}
                                        </p>
                                        @if(isset($ticket['status']))
                                            <span class="text-xs text-gray-400 capitalize">{{ $ticket['status'] }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Empty state when no related data --}}
                    @if(empty($contacts) && empty($projects) && empty($invoices) && empty($tickets))
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
