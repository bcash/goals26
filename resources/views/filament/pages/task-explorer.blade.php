<x-filament-panels::page>
    {{-- Filters --}}
    <div class="flex flex-wrap gap-4 mb-6">
        <div class="w-64">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Project</label>
            <select
                wire:model.live="projectId"
                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
                <option value="">All Projects</option>
                @foreach($this->getProjectOptions() as $id => $name)
                    @if($id !== '')
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endif
                @endforeach
            </select>
        </div>

        <div class="w-64">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Goal</label>
            <select
                wire:model.live="goalId"
                class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
            >
                <option value="">All Goals</option>
                @foreach($this->getGoalOptions() as $id => $title)
                    @if($id !== '')
                        <option value="{{ $id }}">{{ $title }}</option>
                    @endif
                @endforeach
            </select>
        </div>
    </div>

    {{-- Tree Container --}}
    @if(empty($tree))
        <div class="text-center py-12 text-gray-400">
            <x-heroicon-o-rectangle-stack class="w-12 h-12 mx-auto mb-3" />
            <p class="text-lg font-medium mb-1">No tasks found</p>
            <p class="text-sm">Create tasks in the Tasks resource to see them here as a tree.</p>
        </div>
    @else
        <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="space-y-0">
                @foreach($tree as $rootNode)
                    @include('components.task-tree-node', ['node' => $rootNode, 'depth' => 0])
                @endforeach
            </div>
        </div>
    @endif
</x-filament-panels::page>
