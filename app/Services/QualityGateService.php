<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskQualityGate;
use Filament\Notifications\Notification;

class QualityGateService
{
    public function __construct(protected AiService $ai) {}

    /**
     * Trigger a quality gate for a parent task.
     * Called automatically when all children are marked done.
     */
    public function trigger(Task $task): TaskQualityGate
    {
        $childrenCount = $task->children()->count();

        // Update task status
        $task->update(['quality_gate_status' => 'pending']);

        // Generate AI checklist for this task type
        $checklist = $this->generateChecklist($task);

        $gate = TaskQualityGate::create([
            'user_id' => $task->user_id,
            'task_id' => $task->id,
            'triggered_at' => now(),
            'status' => 'pending',
            'checklist' => $checklist,
            'children_completed' => $childrenCount,
            'children_total' => $childrenCount,
        ]);

        // Notify the user via Filament database notification
        if (auth()->check()) {
            Notification::make()
                ->title('Quality gate ready: ' . $task->title)
                ->body('All subtasks complete. Review required before marking done.')
                ->warning()
                ->persistent()
                ->sendToDatabase(auth()->user());
        }

        return $gate;
    }

    /**
     * Submit a quality gate review.
     */
    public function submitReview(
        TaskQualityGate $gate,
        string $status,
        ?string $notes = null
    ): void {
        $passed = $status === 'passed';

        $gate->update([
            'status' => $passed ? 'passed' : 'failed',
            'reviewed_at' => now(),
            'reviewer_notes' => $notes,
            'failure_reason' => $passed ? null : $notes,
        ]);

        $gate->task->update([
            'quality_gate_status' => $passed ? 'passed' : 'failed',
        ]);

        if ($passed) {
            $gate->task->update(['status' => 'done']);
            // Propagate upward -- maybe this triggers a grandparent gate
            app(TaskTreeService::class)->propagateUpward($gate->task);
        } else {
            // Gate failed -- reopen relevant children
            $this->reopenFailedChildren($gate);
        }
    }

    /**
     * Generate a context-aware review checklist using AI.
     */
    public function generateChecklist(Task $task): array
    {
        $prompt = <<<PROMPT
A project task has just had all its subtasks completed.
Generate a quality review checklist for: "{$task->title}"

Project: {$task->project?->name}
Goal: {$task->goal?->title}
Depth in tree: {$task->depth}
Number of subtasks completed: {$task->children()->count()}

Generate 3-6 specific quality check questions a professional should ask before
declaring this task fully complete. Questions should prevent scope gaps, quality
issues, and missed deliverables.

Respond ONLY as a JSON array:
[
  { "question": "string", "answer": null, "passed": null },
  ...
]
PROMPT;

        $response = $this->ai->chat($prompt, 'goal-breakdown');

        // Strip markdown code fences if present
        $clean = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($response));
        $parsed = json_decode($clean, true);

        if (is_array($parsed) && !empty($parsed)) {
            return $parsed;
        }

        return $this->defaultChecklist();
    }

    /**
     * Default checklist when AI generation fails or is unavailable.
     */
    public function defaultChecklist(): array
    {
        return [
            ['question' => 'Does the output match what was originally requested?', 'answer' => null, 'passed' => null],
            ['question' => "Has the work been reviewed against the client's stated requirements?", 'answer' => null, 'passed' => null],
            ['question' => 'Are there any loose ends or undocumented decisions?', 'answer' => null, 'passed' => null],
            ['question' => 'Has this been tested or verified in context?', 'answer' => null, 'passed' => null],
        ];
    }

    /**
     * Reopen children that may need rework after a failed quality gate.
     */
    public function reopenFailedChildren(TaskQualityGate $gate): void
    {
        Task::where('parent_id', $gate->task_id)
            ->where('status', 'done')
            ->update([
                'status' => 'todo',
                'quality_gate_status' => 'failed',
            ]);
    }
}
