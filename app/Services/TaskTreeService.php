<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class TaskTreeService
{
    /**
     * Add a child task to a parent.
     * Updates parent's is_leaf flag and recalculates paths.
     */
    public function addChild(Task $parent, array $attributes): Task
    {
        $child = Task::create(array_merge($attributes, [
            'user_id' => $parent->user_id,
            'parent_id' => $parent->id,
            'depth' => $parent->depth + 1,
        ]));

        // Parent is no longer a leaf
        if ($parent->is_leaf) {
            $parent->update([
                'is_leaf' => false,
                'decomposition_status' => 'needs_breakdown',
                'two_minute_check' => false,
            ]);
        }

        $this->rebuildPath($child);

        return $child;
    }

    /**
     * Build the materialized path for a task.
     * Path = all ancestor IDs separated by slashes: "1/4/12/"
     */
    public function rebuildPath(Task $task): void
    {
        $path = '';

        if ($task->parent_id) {
            $parent = Task::withoutGlobalScopes()->find($task->parent_id);
            $path = ($parent?->path ?? '') . $parent?->id . '/';
        }

        $task->update(['path' => $path . $task->id . '/']);
    }

    /**
     * Get the full task tree, optionally filtered by project or goal.
     * Returns a flat collection of root-level tasks with nested children.
     */
    public function getTree(?int $projectId = null, ?int $goalId = null): Collection
    {
        $roots = Task::whereNull('parent_id')
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->when($goalId, fn ($q) => $q->where('goal_id', $goalId))
            ->orderBy('priority', 'desc')
            ->orderBy('sort_order')
            ->get();

        if ($roots->isEmpty()) {
            return collect();
        }

        // Fetch all descendant tasks in a single query for efficiency
        $allDescendantIds = $roots->pluck('id');
        $allTasks = Task::whereIn('parent_id', function ($query) use ($projectId, $goalId) {
            $query->select('id')
                ->from('tasks')
                ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
                ->when($goalId, fn ($q) => $q->where('goal_id', $goalId));
        })
            ->orWhere(function ($q) use ($projectId, $goalId) {
                $q->whereNull('parent_id')
                    ->when($projectId, fn ($q2) => $q2->where('project_id', $projectId))
                    ->when($goalId, fn ($q2) => $q2->where('goal_id', $goalId));
            })
            ->orderBy('depth')
            ->orderBy('sort_order')
            ->get();

        // Build nested tree from flat collection
        return $this->nestChildren($allTasks, null);
    }

    /**
     * Nest children recursively from a flat collection.
     */
    public function nestChildren(Collection $tasks, ?int $parentId = null): Collection
    {
        return $tasks
            ->where('parent_id', $parentId)
            ->map(function ($task) use ($tasks) {
                $task->setRelation('children', $this->nestChildren($tasks, $task->id));
                return $task;
            })
            ->values();
    }

    /**
     * Mark a leaf task done and propagate up the tree.
     * Triggers quality gate if all siblings are complete.
     */
    public function completeLeaf(Task $task): void
    {
        if (!$task->is_leaf) {
            throw new \LogicException('Only leaf tasks can be directly completed.');
        }

        $task->update(['status' => 'done']);

        $this->propagateUpward($task);
    }

    /**
     * Walk up the tree. If all siblings are done, trigger the parent's quality gate.
     */
    public function propagateUpward(Task $task): void
    {
        if (!$task->parent_id) {
            return;
        }

        $parent = Task::find($task->parent_id);

        if (!$parent) {
            return;
        }

        $siblings = Task::where('parent_id', $parent->id)->get();
        $allDone = $siblings->every(fn ($s) => $s->status === 'done');

        if ($allDone) {
            app(QualityGateService::class)->trigger($parent);
        }
    }

    /**
     * Get all leaf-level tasks that are ready to be scheduled --
     * the actionable work queue for the current user.
     */
    public function getActionableLeaves(?int $projectId = null): EloquentCollection
    {
        return Task::where('is_leaf', true)
            ->where('two_minute_check', true)
            ->whereIn('status', ['todo', 'in-progress'])
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->orderByRaw("array_position(ARRAY['critical','high','medium','low']::varchar[], priority)")
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Get all tasks that still need to be broken down --
     * the decomposition queue.
     */
    public function getNeedsBreakdown(): EloquentCollection
    {
        return Task::where('decomposition_status', 'needs_breakdown')
            ->where('is_leaf', true)
            ->orderBy('depth', 'desc')
            ->get();
    }

    /**
     * Reorder siblings by an array of ordered IDs.
     */
    public function reorder(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Task::where('id', $id)->update(['sort_order' => $index]);
        }
    }

    /**
     * Get all tasks in a tree that are deferred,
     * along with their deferral context.
     */
    public function getDeferredBranches(?int $projectId = null): EloquentCollection
    {
        return Task::where('status', 'deferred')
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->with(['parent', 'deferredItem'])
            ->orderBy('depth')
            ->get();
    }

    /**
     * Estimate the total potential value of all deferred branches
     * in a project's task tree.
     */
    public function deferredTreeValue(?int $projectId = null): float
    {
        return \App\Models\DeferredItem::where('project_id', $projectId)
            ->whereNotIn('status', ['archived', 'lost'])
            ->sum('estimated_value') ?? 0;
    }
}
