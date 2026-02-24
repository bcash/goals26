@php
    $statusColors = [
        'todo'        => 'text-gray-600 dark:text-gray-400',
        'in-progress' => 'text-warning-500',
        'done'        => 'text-success-500 line-through',
        'deferred'    => 'text-info-400',
    ];
    $isLeaf   = empty($node['children']);
    $isDone   = ($node['status'] ?? '') === 'done';
    $hasGate  = ($node['quality_gate_status'] ?? 'not_triggered') === 'pending';
    $isExpanded = in_array($node['id'], $expandedNodes ?? []);
    $hasChildren = !empty($node['children']);
@endphp

<div style="padding-left: {{ $depth * 1.5 }}rem" class="py-0.5">
    <div class="flex items-center gap-2 group rounded-lg px-2 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">

        {{-- Expand/collapse toggle --}}
        @if($hasChildren)
            <button
                wire:click="toggleNode({{ $node['id'] }})"
                class="w-5 h-5 flex items-center justify-center text-gray-400 hover:text-gray-600 transition-colors flex-shrink-0"
            >
                @if($isExpanded)
                    <x-heroicon-s-chevron-down class="w-4 h-4" />
                @else
                    <x-heroicon-s-chevron-right class="w-4 h-4" />
                @endif
            </button>
        @else
            <span class="w-5 flex-shrink-0"></span>
        @endif

        {{-- Complete button (leaves only) --}}
        @if($isLeaf && !$isDone)
            <button
                wire:click="completeTask({{ $node['id'] }})"
                class="w-5 h-5 rounded border-2 border-gray-300 hover:border-success-500 hover:bg-success-50 flex-shrink-0 transition-colors"
            ></button>
        @elseif($isDone)
            <span class="text-success-500 flex-shrink-0">
                <x-heroicon-s-check-circle class="w-5 h-5" />
            </span>
        @else
            <span class="w-5 h-5 flex items-center justify-center text-gray-300 flex-shrink-0">
                <x-heroicon-o-folder class="w-4 h-4" />
            </span>
        @endif

        {{-- Title --}}
        <span class="text-sm flex-1 {{ $statusColors[$node['status'] ?? 'todo'] ?? 'text-gray-700 dark:text-gray-300' }}">
            {{ $node['title'] }}
        </span>

        {{-- Priority badge --}}
        @if(in_array($node['priority'] ?? '', ['high', 'critical']))
            <span class="text-xs px-1.5 py-0.5 rounded-full
                {{ ($node['priority'] ?? '') === 'critical' ? 'bg-danger-100 text-danger-700' : 'bg-warning-100 text-warning-700' }}">
                {{ ucfirst($node['priority'] ?? '') }}
            </span>
        @endif

        {{-- Quality gate badge --}}
        @if($hasGate)
            <span class="text-xs px-2 py-0.5 rounded-full bg-warning-100 text-warning-700 font-semibold">
                Review Required
            </span>
        @endif

        {{-- Decompose button (unready leaves) --}}
        @if($isLeaf && !($node['two_minute_check'] ?? false) && !$isDone)
            <button
                wire:click="addChild({{ $node['id'] }})"
                class="text-xs text-primary-500 hover:underline opacity-0 group-hover:opacity-100 transition-opacity"
            >
                Break down
            </button>
        @endif

        {{-- Add child button (for parent nodes) --}}
        @if($hasChildren)
            <button
                wire:click="addChild({{ $node['id'] }})"
                class="text-xs text-gray-400 hover:text-primary-500 opacity-0 group-hover:opacity-100 transition-opacity"
                title="Add subtask"
            >
                <x-heroicon-o-plus class="w-4 h-4" />
            </button>
        @endif

        {{-- Leaf status badge --}}
        @if($isLeaf && ($node['two_minute_check'] ?? false) && !$isDone)
            <span class="text-xs text-success-400 opacity-0 group-hover:opacity-100">Ready</span>
        @endif
    </div>

    {{-- Recursively render children --}}
    @if($hasChildren && $isExpanded)
        @foreach($node['children'] as $child)
            @include('components.task-tree-node', ['node' => $child, 'depth' => $depth + 1])
        @endforeach
    @endif
</div>
