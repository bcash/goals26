<?php

namespace App\Mcp\Tools;

use App\Models\Task;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateTaskContext extends Tool
{
    protected string $name = 'update-task-context';

    protected string $description = 'Save or update the working context for a task. Use this to persist key files, specifications, requirements, decisions, and constraints so future sessions have full context.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()
                ->description('The ID of the task to update')
                ->required(),
            'context' => $schema->string()
                ->description('The working context in Markdown format. Include: related models, service dependencies, key files, requirements, constraints, and any decisions made during implementation.')
                ->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'task_id' => 'required|integer|exists:tasks,id',
            'context' => 'required|string',
        ]);

        $task = Task::withoutGlobalScopes()->findOrFail($validated['task_id']);

        $task->update(['context' => $validated['context']]);

        return Response::json([
            'success' => true,
            'task_id' => $task->id,
            'title' => $task->title,
            'context_length' => strlen($validated['context']),
            'message' => "Context saved for task #{$task->id}: {$task->title}",
        ]);
    }
}
