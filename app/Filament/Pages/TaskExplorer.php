<?php

namespace App\Filament\Pages;

use App\Models\Goal;
use App\Models\Project;
use App\Models\Task;
use App\Services\TaskTreeService;
use Filament\Pages\Page;

class TaskExplorer extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Goals & Projects';

    protected static ?string $navigationLabel = 'Task Explorer';

    protected static ?string $title = 'Task Explorer';

    protected static ?int $navigationSort = 5;

    protected string $view = 'filament.pages.task-explorer';

    public ?int $projectId = null;

    public ?int $goalId = null;

    public array $tree = [];

    public array $expandedNodes = [];

    public function mount(): void
    {
        $this->loadTree();
    }

    public function updatedProjectId(): void
    {
        $this->loadTree();
    }

    public function updatedGoalId(): void
    {
        $this->loadTree();
    }

    public function loadTree(): void
    {
        $service = app(TaskTreeService::class);
        $treeCollection = $service->getTree(
            projectId: $this->projectId ?: null,
            goalId: $this->goalId ?: null,
        );

        $this->tree = $treeCollection->toArray();
    }

    public function toggleNode(int $taskId): void
    {
        if (in_array($taskId, $this->expandedNodes)) {
            $this->expandedNodes = array_values(array_diff($this->expandedNodes, [$taskId]));
        } else {
            $this->expandedNodes[] = $taskId;
        }
    }

    public function completeTask(int $taskId): void
    {
        $task = Task::findOrFail($taskId);

        if ($task->is_leaf) {
            $service = app(TaskTreeService::class);
            $service->completeLeaf($task);
        } else {
            $task->update(['status' => $task->status === 'done' ? 'todo' : 'done']);
        }

        $this->loadTree();
    }

    public function addChild(int $parentId): void
    {
        $parent = Task::findOrFail($parentId);
        $service = app(TaskTreeService::class);

        $service->addChild($parent, [
            'title' => 'New subtask',
            'status' => 'todo',
            'priority' => $parent->priority,
            'life_area_id' => $parent->life_area_id,
            'project_id' => $parent->project_id,
            'goal_id' => $parent->goal_id,
        ]);

        if (! in_array($parentId, $this->expandedNodes)) {
            $this->expandedNodes[] = $parentId;
        }

        $this->loadTree();
    }

    public function getProjectOptions(): array
    {
        return Project::orderBy('name')
            ->pluck('name', 'id')
            ->prepend('All Projects', '')
            ->toArray();
    }

    public function getGoalOptions(): array
    {
        return Goal::where('status', 'active')
            ->orderBy('title')
            ->pluck('title', 'id')
            ->prepend('All Goals', '')
            ->toArray();
    }
}
