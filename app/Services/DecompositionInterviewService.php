<?php

namespace App\Services;

use App\Models\Task;
use Illuminate\Support\Collection;

class DecompositionInterviewService
{
    public function __construct(protected AiService $ai) {}

    /**
     * Start a decomposition interview for a task.
     * Returns the first question to ask the user (or direct subtask suggestions).
     */
    public function start(Task $task): array
    {
        $context = $this->buildContext($task);
        $prompt = $this->buildStartPrompt($task, $context);

        $response = $this->ai->chat($prompt, 'goal-breakdown', [
            'task_id' => $task->id,
            'task_title' => $task->title,
            'depth' => $task->depth,
            'ancestors' => $task->ancestors()->pluck('title')->toArray(),
        ]);

        // Parse the JSON response
        $parsed = $this->parseJsonResponse($response);

        return [
            'question' => $parsed['question'] ?? null,
            'suggested_subtasks' => $parsed['suggested_subtasks'] ?? [],
            'is_ready' => $parsed['is_ready'] ?? false,
            'rationale' => $parsed['rationale'] ?? '',
        ];
    }

    /**
     * Process a user's answer during decomposition and return the next step.
     */
    public function answer(Task $task, string $questionKey, string $userAnswer): array
    {
        $prompt = $this->buildAnswerPrompt($task, $userAnswer, [
            ['question' => $questionKey, 'answer' => $userAnswer],
        ]);

        $response = $this->ai->chat($prompt, 'goal-breakdown');
        $parsed = $this->parseJsonResponse($response);

        $verdict = $parsed['verdict'] ?? 'continue';

        // If AI says the task is small enough, flag it
        if ($verdict === 'ready') {
            $task->update([
                'two_minute_check' => true,
                'decomposition_status' => 'ready',
            ]);

            return [
                'verdict' => 'ready',
                'message' => $parsed['message'] ?? 'This task is ready to be scheduled.',
            ];
        }

        // If AI suggests subtasks, return them for user confirmation
        if (!empty($parsed['suggested_subtasks'])) {
            return [
                'verdict' => 'needs_children',
                'suggested_subtasks' => $parsed['suggested_subtasks'],
                'rationale' => $parsed['rationale'] ?? '',
            ];
        }

        // More questions needed
        return [
            'verdict' => 'continue',
            'question' => $parsed['question'] ?? 'Can you describe what "done" looks like for this task?',
        ];
    }

    /**
     * Accept suggested subtasks and create them as children of the parent task.
     */
    public function acceptSubtasks(Task $parent, array $subtasks): Collection
    {
        $treeService = app(TaskTreeService::class);
        $created = collect();

        foreach ($subtasks as $index => $title) {
            // Handle both string titles and array items with 'title' key
            $subtaskTitle = is_array($title) ? ($title['title'] ?? $title[0] ?? '') : $title;

            if (empty($subtaskTitle)) {
                continue;
            }

            $child = $treeService->addChild($parent, [
                'title' => $subtaskTitle,
                'status' => 'todo',
                'priority' => $parent->priority,
                'life_area_id' => $parent->life_area_id,
                'project_id' => $parent->project_id,
                'goal_id' => $parent->goal_id,
                'sort_order' => $index,
                'decomposition_status' => 'needs_breakdown',
                'is_leaf' => true,
                'billable' => $parent->billable,
            ]);

            $created->push($child);
        }

        return $created;
    }

    // -- Prompt Builders --

    private function buildStartPrompt(Task $task, array $context): string
    {
        return <<<PROMPT
You are a project planning assistant helping break down a task using structured decomposition.

TASK TO BREAK DOWN: "{$task->title}"
DEPTH IN TREE: {$task->depth} (0 = top-level BHAG, higher = more detailed)
PARENT CONTEXT: {$context['parent_chain']}
PROJECT: {$context['project']}
GOAL: {$context['goal']}

Your job is to help determine whether this task:
1. Is already small enough to be done in under 2 minutes or in one focused sitting -- in which case mark it READY
2. Needs to be broken into subtasks -- in which case suggest 2-6 clear subtasks

Ask ONE clarifying question if needed, OR if the task is clear enough, suggest subtasks directly.

Respond ONLY in this JSON format:
{
  "question": "string or null",
  "suggested_subtasks": ["string", "string"] or [],
  "is_ready": true or false,
  "rationale": "brief explanation"
}
PROMPT;
    }

    private function buildAnswerPrompt(Task $task, string $answer, array $history): string
    {
        $historyText = collect($history)
            ->map(fn ($h) => "Q: {$h['question']}\nA: {$h['answer']}")
            ->join("\n\n");

        return <<<PROMPT
You are decomposing the task: "{$task->title}"

CONVERSATION SO FAR:
{$historyText}

USER JUST ANSWERED: "{$answer}"

Based on this, determine:
- If the task is now clearly small enough (under 2 minutes or one focused sitting) -> verdict: "ready"
- If the task needs to be split into subtasks -> verdict: "needs_children" with suggested_subtasks
- If you need more information -> verdict: "continue" with another question

Respond ONLY in this JSON format:
{
  "verdict": "ready" | "needs_children" | "continue",
  "message": "brief message to show user",
  "question": "next question if verdict is continue, otherwise null",
  "suggested_subtasks": ["string"] or [],
  "rationale": "brief explanation"
}
PROMPT;
    }

    private function buildContext(Task $task): array
    {
        $parentChain = $task->ancestors()->pluck('title')->implode(' > ');
        $project = $task->project?->name ?? 'No project';
        $goal = $task->goal?->title ?? 'No linked goal';

        return [
            'parent_chain' => $parentChain ?: 'Root level',
            'project' => $project,
            'goal' => $goal,
        ];
    }

    /**
     * Parse a JSON response from the AI, handling potential formatting issues.
     */
    private function parseJsonResponse(string $response): array
    {
        // Strip markdown code fences if present
        $clean = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($response));

        $parsed = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // If JSON parsing fails, return a fallback structure
            return [
                'question' => 'Can you describe what "done" looks like for this task in concrete terms?',
                'suggested_subtasks' => [],
                'is_ready' => false,
                'verdict' => 'continue',
                'rationale' => 'Could not parse AI response; asking a follow-up question.',
            ];
        }

        return $parsed;
    }
}
