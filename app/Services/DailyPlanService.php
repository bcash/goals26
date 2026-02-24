<?php

namespace App\Services;

use App\Models\DailyPlan;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

class DailyPlanService
{
    public function __construct(protected AiService $ai) {}

    /**
     * Get today's plan or create a draft if none exists.
     */
    public function getOrCreateToday(User $user): DailyPlan
    {
        return DailyPlan::firstOrCreate(
            [
                'user_id' => $user->id,
                'plan_date' => today()->toDateString(),
            ],
            [
                'status' => 'draft',
            ]
        );
    }

    /**
     * Build a daily plan using AI suggestions.
     * Populates the plan with AI-generated theme, intention, and priorities.
     */
    public function buildFromAi(User $user): DailyPlan
    {
        $plan = $this->getOrCreateToday($user);

        // Get AI suggestion for the daily plan
        $aiSuggestion = $this->ai->generateDailyPlan($user);

        // Get the top actionable tasks for today
        $topTasks = Task::where('is_leaf', true)
            ->whereIn('status', ['todo', 'in-progress'])
            ->where('two_minute_check', true)
            ->orderByRaw("array_position(ARRAY['critical','high','medium','low']::varchar[], priority)")
            ->orderBy('due_date')
            ->limit(3)
            ->get();

        // Set the plan's priorities from top tasks
        $plan->update([
            'top_priority_1' => $topTasks->get(0)?->id,
            'top_priority_2' => $topTasks->get(1)?->id,
            'top_priority_3' => $topTasks->get(2)?->id,
            'ai_morning_prompt' => $aiSuggestion,
            'day_theme' => $this->suggestDayTheme(),
            'status' => 'active',
        ]);

        return $plan->refresh();
    }

    /**
     * Get the top priorities for a daily plan as a collection of tasks.
     */
    public function getTopPriorities(DailyPlan $plan): Collection
    {
        return collect([
            $plan->priority1,
            $plan->priority2,
            $plan->priority3,
        ])->filter();
    }

    /**
     * Mark a priority as complete by its index (1, 2, or 3).
     */
    public function completePriority(DailyPlan $plan, int $index): void
    {
        $priorityField = "top_priority_{$index}";
        $taskId = $plan->{$priorityField};

        if (!$taskId) {
            return;
        }

        $task = Task::find($taskId);

        if ($task) {
            $task->update(['status' => 'done']);

            // If task is a leaf in the tree, propagate completion upward
            if ($task->is_leaf && $task->parent_id) {
                app(TaskTreeService::class)->propagateUpward($task);
            }
        }
    }

    /**
     * Suggest a day theme based on the day of the week and active goals.
     */
    private function suggestDayTheme(): string
    {
        $dayThemes = [
            'Monday' => 'Focus & Planning',
            'Tuesday' => 'Deep Work',
            'Wednesday' => 'Creative Flow',
            'Thursday' => 'Collaboration',
            'Friday' => 'Completion & Review',
            'Saturday' => 'Personal Growth',
            'Sunday' => 'Rest & Reflection',
        ];

        return $dayThemes[now()->format('l')] ?? 'Intentional Action';
    }
}
