<?php

namespace App\Mcp\Tools;

use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateTaskPlan extends Tool
{
    protected string $name = 'update-task-plan';

    protected string $description = 'Save or update the implementation plan for a task. Use this to persist planning context (steps, approach, architecture decisions) so future sessions can pick up where you left off.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()
                ->description('The ID of the task to update')
                ->required(),
            'plan' => $schema->string()
                ->description('The implementation plan in Markdown format. Include: approach, key files, implementation steps, decisions made, and open questions.')
                ->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'task_id' => 'required|integer|exists:tasks,id',
            'plan' => 'required|string',
        ]);

        $task = Task::withoutGlobalScopes()->findOrFail($validated['task_id']);

        $task->update(['plan' => $validated['plan']]);

        return Response::json([
            'success' => true,
            'task_id' => $task->id,
            'title' => $task->title,
            'plan_length' => strlen($validated['plan']),
            'message' => "Plan saved for task #{$task->id}: {$task->title}",
        ]);
    }
}
