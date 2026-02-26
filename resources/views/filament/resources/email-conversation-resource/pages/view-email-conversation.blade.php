<x-filament-panels::page>
    @php
        $conversation = $this->record;
        $contact = $this->getContact();
        $threads = $this->getThreads();
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Left Column: Main Content --}}
        <div class="lg:col-span-3 space-y-4">
            {{-- Subject Header --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center gap-3 flex-wrap">
                    <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                        {{ $conversation->subject }}
                    </h2>
                    <span class="text-sm text-gray-400 font-mono">#{{ $conversation->freescout_conversation_id }}</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ match($conversation->status) {
                            'active' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                            'pending' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
                            'closed' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                            'spam' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
                            default => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                        } }}">
                        {{ ucfirst($conversation->status) }}
                    </span>
                </div>
            </div>

            {{-- Reply Form --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Reply</h3>
                <div class="space-y-3">
                    <textarea
                        wire:model="replyBody"
                        rows="4"
                        placeholder="Type your reply..."
                        class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    ></textarea>

                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <select
                                wire:model="replyStatus"
                                class="rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option value="active">Active</option>
                                <option value="pending">Pending</option>
                                <option value="closed">Closed</option>
                            </select>

                            <button
                                wire:click="toggleNoteForm"
                                type="button"
                                class="text-sm text-gray-500 hover:text-yellow-600 dark:text-gray-400 dark:hover:text-yellow-400 transition-colors"
                            >
                                <x-heroicon-o-pencil-square class="w-4 h-4 inline-block mr-1" />
                                Note
                            </button>
                        </div>

                        <button
                            wire:click="sendReply"
                            wire:loading.attr="disabled"
                            wire:target="sendReply"
                            class="inline-flex items-center px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 disabled:opacity-50 transition-colors"
                        >
                            <span wire:loading.remove wire:target="sendReply">Send Reply</span>
                            <span wire:loading wire:target="sendReply">
                                <x-heroicon-o-arrow-path class="w-4 h-4 animate-spin inline-block mr-1" />
                                Sending...
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Note Form (toggleable) --}}
            @if($this->showNoteForm)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl shadow-sm border border-yellow-200 dark:border-yellow-700 p-4">
                    <h3 class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-3">
                        <x-heroicon-o-pencil-square class="w-4 h-4 inline-block mr-1" />
                        Internal Note
                    </h3>
                    <div class="space-y-3">
                        <textarea
                            wire:model="noteBody"
                            rows="3"
                            placeholder="Add an internal note (not visible to customer)..."
                            class="w-full rounded-lg border-yellow-300 dark:border-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-200 text-sm shadow-sm focus:border-yellow-500 focus:ring-yellow-500"
                        ></textarea>

                        <div class="flex items-center gap-2">
                            <button
                                wire:click="addNote"
                                wire:loading.attr="disabled"
                                wire:target="addNote"
                                class="inline-flex items-center px-3 py-1.5 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700 disabled:opacity-50 transition-colors"
                            >
                                <span wire:loading.remove wire:target="addNote">Save Note</span>
                                <span wire:loading wire:target="addNote">Saving...</span>
                            </button>
                            <button
                                wire:click="toggleNoteForm"
                                type="button"
                                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-colors"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Thread Timeline --}}
            <div class="space-y-4">
                @forelse($threads as $thread)
                    @php
                        $isCustomer = $thread->type === 'customer';
                        $isNote = $thread->type === 'note';
                        $isAgent = $thread->type === 'agent' || $thread->type === 'message';
                        $initial = strtoupper(substr($thread->from_name ?? $thread->from_email ?? '?', 0, 1));
                    @endphp

                    <div class="rounded-xl shadow-sm border overflow-hidden
                        {{ $isNote
                            ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-700'
                            : ($isAgent
                                ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-700'
                                : 'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-700')
                        }}">
                        {{-- Thread Header --}}
                        <div class="px-4 py-3 border-b
                            {{ $isNote
                                ? 'border-yellow-200 dark:border-yellow-700'
                                : ($isAgent
                                    ? 'border-blue-200 dark:border-blue-700'
                                    : 'border-gray-200 dark:border-gray-700')
                            }}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    {{-- Avatar --}}
                                    <div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold text-white
                                        {{ $isNote
                                            ? 'bg-yellow-500'
                                            : ($isAgent ? 'bg-blue-500' : 'bg-gray-500')
                                        }}">
                                        {{ $initial }}
                                    </div>
                                    <div>
                                        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                            {{ $thread->from_name ?? 'Unknown' }}
                                        </span>
                                        @if($thread->from_email)
                                            <span class="text-xs text-gray-500 dark:text-gray-400 ml-1">
                                                &lt;{{ $thread->from_email }}&gt;
                                            </span>
                                        @endif
                                        @if($isNote)
                                            <span class="ml-2 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-yellow-200 text-yellow-800 dark:bg-yellow-800 dark:text-yellow-200">
                                                Note
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $thread->message_at?->diffForHumans() ?? '' }}
                                </span>
                            </div>

                            @if(!$isNote && $thread->to_emails)
                                <div class="mt-1 ml-11 text-xs text-gray-500 dark:text-gray-400">
                                    To: {{ is_array($thread->to_emails) ? implode(', ', $thread->to_emails) : $thread->to_emails }}
                                </div>
                            @endif
                        </div>

                        {{-- Thread Body --}}
                        <div class="p-4">
                            <div class="prose prose-sm dark:prose-invert max-w-none break-words">
                                {!! $thread->body !!}
                            </div>

                            @if($thread->has_attachments)
                                <div class="mt-3 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                                    <x-heroicon-o-paper-clip class="w-3.5 h-3.5" />
                                    {{ $thread->attachment_count }} attachment{{ $thread->attachment_count !== 1 ? 's' : '' }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
                        <x-heroicon-o-chat-bubble-bottom-center-text class="w-12 h-12 mx-auto mb-3 text-gray-400" />
                        <p class="text-gray-500 dark:text-gray-400">No threads found for this conversation.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Right Sidebar --}}
        <div class="lg:col-span-1 space-y-4">
            {{-- Contact Card --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-3">
                    Contact
                </h3>

                @if($contact)
                    <div class="space-y-2">
                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ $contact->fullName() }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <x-heroicon-o-envelope class="w-3.5 h-3.5 inline-block mr-1" />
                            {{ $contact->email }}
                        </p>
                        @if($contact->company)
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <x-heroicon-o-building-office class="w-3.5 h-3.5 inline-block mr-1" />
                                {{ $contact->company }}
                            </p>
                        @endif
                        @if($contact->phone)
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                <x-heroicon-o-phone class="w-3.5 h-3.5 inline-block mr-1" />
                                {{ $contact->phone }}
                            </p>
                        @endif
                        @if($contact->contact_type)
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                {{ ucfirst($contact->contact_type) }}
                            </span>
                        @endif
                        <p class="text-xs text-gray-400 mt-2">
                            {{ $contact->conversation_count ?? 0 }} conversation{{ ($contact->conversation_count ?? 0) !== 1 ? 's' : '' }}
                        </p>
                    </div>
                @else
                    <p class="text-sm text-gray-400">No contact linked</p>
                @endif
            </div>

            {{-- Conversation Details --}}
            <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-3">
                    Details
                </h3>
                <div class="space-y-3">
                    @if($conversation->assigned_to_name)
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Assigned To</p>
                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $conversation->assigned_to_name }}</p>
                            @if($conversation->assigned_to_email)
                                <p class="text-xs text-gray-400">{{ $conversation->assigned_to_email }}</p>
                            @endif
                        </div>
                    @else
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Assigned To</p>
                            <p class="text-sm text-yellow-600 dark:text-yellow-400">Unassigned</p>
                        </div>
                    @endif

                    @if($conversation->category)
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Category</p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">
                                {{ ucfirst($conversation->category) }}
                            </span>
                        </div>
                    @endif

                    <div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Threads</p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $conversation->thread_count ?? $threads->count() }}</p>
                    </div>

                    @if($conversation->ai_sentiment)
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Sentiment</p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ match($conversation->ai_sentiment) {
                                    'positive' => 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
                                    'neutral' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                                    'negative' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300',
                                    'escalation' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
                                    default => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                                } }}">
                                {{ ucfirst($conversation->ai_sentiment) }}
                            </span>
                        </div>
                    @endif

                    @if($conversation->importance && $conversation->importance !== 'normal')
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Importance</p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                {{ match($conversation->importance) {
                                    'critical' => 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
                                    'high' => 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300',
                                    'low' => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                                    default => 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
                                } }}">
                                {{ ucfirst($conversation->importance) }}
                            </span>
                        </div>
                    @endif

                    @if($conversation->last_message_at)
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Last Message</p>
                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $conversation->last_message_at->diffForHumans() }}</p>
                        </div>
                    @endif

                    @if($conversation->last_synced_at)
                        <div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">Last Synced</p>
                            <p class="text-xs text-gray-400">{{ $conversation->last_synced_at->diffForHumans() }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
