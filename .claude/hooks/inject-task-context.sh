#!/bin/bash
# Hook: SessionStart
# Queries the database for in-progress tasks that have plan/context populated
# and injects them into Claude's session context for continuity.

cd "$CLAUDE_PROJECT_DIR" 2>/dev/null || cd "$(dirname "$0")/../.." 2>/dev/null || exit 0

# Query tasks with active context using PHP/Artisan
CONTEXT=$(php artisan tinker --execute="
    \$tasks = App\Models\Task::withoutGlobalScopes()
        ->where('status', 'in-progress')
        ->where(function(\$q) {
            \$q->whereNotNull('plan')->orWhereNotNull('context');
        })
        ->select('id', 'title', 'plan', 'context', 'status', 'project_id')
        ->with('project:id,name')
        ->limit(5)
        ->get();

    if (\$tasks->isEmpty()) {
        echo '';
        return;
    }

    echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;
    echo '📋 ACTIVE TASK CONTEXT (from previous sessions)' . PHP_EOL;
    echo '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━' . PHP_EOL;

    foreach (\$tasks as \$task) {
        \$project = \$task->project ? \$task->project->name : 'No project';
        echo PHP_EOL . '## Task #' . \$task->id . ': ' . \$task->title . PHP_EOL;
        echo '**Project:** ' . \$project . ' | **Status:** ' . \$task->status . PHP_EOL;

        if (\$task->plan) {
            echo PHP_EOL . '### Implementation Plan' . PHP_EOL;
            echo \$task->plan . PHP_EOL;
        }

        if (\$task->context) {
            echo PHP_EOL . '### Working Context' . PHP_EOL;
            echo \$task->context . PHP_EOL;
        }

        echo PHP_EOL . '---' . PHP_EOL;
    }

    echo PHP_EOL . 'To continue working on a task, reference it by name or ID.' . PHP_EOL;
    echo 'Use update-task-plan and update-task-context MCP tools to save progress.' . PHP_EOL;
" 2>/dev/null)

if [ -n "$CONTEXT" ]; then
    echo "$CONTEXT"
fi

exit 0
