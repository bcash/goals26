<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Active Goals</x-slot>
        <x-slot name="headerEnd">
            <a href="{{ route('filament.admin.resources.goals.index') }}"
               class="text-xs text-primary-500 hover:underline">All Goals</a>
        </x-slot>

        @if($goals->isEmpty())
            <div class="text-center py-8 text-gray-400">
                <x-heroicon-o-flag class="w-8 h-8 mx-auto mb-2" />
                <p class="text-sm">No active goals yet.</p>
                <a href="{{ route('filament.admin.resources.goals.create') }}"
                   class="text-primary-500 text-sm hover:underline">Create your first goal</a>
            </div>
        @else
            <div class="space-y-4">
                @foreach($goals as $goal)
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <div class="flex items-center gap-2 min-w-0">
                                @if($goal->lifeArea?->icon)
                                    <span style="color: {{ $goal->lifeArea->color_hex ?? '#6B7280' }}">
                                        @svg('heroicon-o-' . $goal->lifeArea->icon, 'w-5 h-5')
                                    </span>
                                @endif
                                <a href="{{ route('filament.admin.resources.goals.view', $goal) }}"
                                   class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate hover:text-primary-500">
                                    {{ $goal->title }}
                                </a>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0 ml-3">
                                <span class="text-xs text-gray-400">{{ $goal->horizon }}</span>
                                <span class="text-sm font-bold"
                                      style="color: {{ $goal->lifeArea?->color_hex ?? '#C9A84C' }}">
                                    {{ $goal->progress_percent }}%
                                </span>
                            </div>
                        </div>

                        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2">
                            <div
                                class="h-2 rounded-full transition-all duration-500"
                                style="
                                    width: {{ $goal->progress_percent }}%;
                                    background-color: {{ $goal->lifeArea?->color_hex ?? '#C9A84C' }};
                                "
                            ></div>
                        </div>

                        @if($goal->why)
                            <p class="text-xs text-gray-400 mt-1 italic truncate">
                                "{{ $goal->why }}"
                            </p>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
