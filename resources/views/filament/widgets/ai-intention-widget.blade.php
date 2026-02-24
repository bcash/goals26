<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            AI Morning Intention
        </x-slot>

        <x-slot name="headerEnd">
            @if($intention)
                <button
                    wire:click="generate"
                    wire:loading.attr="disabled"
                    class="text-xs text-gray-400 hover:text-primary-500 transition-colors"
                >
                    Regenerate
                </button>
            @endif
        </x-slot>

        @if($isGenerating)
            <div class="flex items-center gap-3 py-4">
                <div class="w-5 h-5 border-2 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
                <span class="text-sm text-gray-500 italic">
                    Solas Run is reading your goals and crafting today's intention...
                </span>
            </div>

        @elseif($intention)
            <blockquote class="border-l-4 border-warning-400 pl-4 py-1 my-2">
                <p class="text-base text-gray-700 dark:text-gray-300 italic leading-relaxed">
                    {{ $intention }}
                </p>
            </blockquote>

        @else
            <div class="text-center py-6">
                <p class="text-sm text-gray-400 mb-4">
                    Your AI morning intention hasn't been generated yet.<br>
                    It will draw from your active goals, recent reflections, and today's priorities.
                </p>
                <button
                    wire:click="generate"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg
                           bg-warning-500 hover:bg-warning-600 text-white text-sm font-medium
                           transition-colors disabled:opacity-50"
                >
                    <x-heroicon-o-sparkles class="w-4 h-4" />
                    <span wire:loading.remove>Generate Morning Intention</span>
                    <span wire:loading>Generating...</span>
                </button>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
