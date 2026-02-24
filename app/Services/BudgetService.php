<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\TimeEntry;
use Filament\Notifications\Notification;

class BudgetService
{
    /**
     * Recalculate all budget metrics for a project budget.
     * Called whenever a time entry is created or updated.
     */
    public function recalculate(ProjectBudget $budget): void
    {
        $project = $budget->project;

        if (!$project) {
            return;
        }

        $totalHours = TimeEntry::where('project_id', $project->id)->sum('hours');
        $actualSpend = TimeEntry::where('project_id', $project->id)
            ->where('billable', true)
            ->sum('cost');

        $remaining = ($budget->budget_total ?? 0) - $actualSpend;

        // Burn rate: average daily spend over the project lifetime
        $projectAge = max(1, $project->created_at->diffInDays(now()));
        $burnRate = round($actualSpend / $projectAge, 2);

        $budget->update([
            'actual_spend' => $actualSpend,
            'estimated_remaining' => max(0, $remaining),
            'burn_rate' => $burnRate,
        ]);

        if ($budget->isNearAlert()) {
            $this->sendBudgetAlert($budget);
        }
    }

    /**
     * Log time against a task and trigger budget recalculation.
     */
    public function logTime(array $data): TimeEntry
    {
        $projectId = $data['project_id'] ?? null;
        $project = $projectId ? Project::find($projectId) : null;
        $rate = $data['hourly_rate'] ?? $project?->budget?->hourly_rate ?? 0;
        $hours = $data['hours'] ?? 0;

        $entry = TimeEntry::create([
            'user_id' => auth()->id(),
            'project_id' => $projectId,
            'task_id' => $data['task_id'] ?? null,
            'description' => $data['description'] ?? '',
            'hours' => $hours,
            'logged_date' => $data['logged_date'] ?? today(),
            'billable' => $data['billable'] ?? true,
            'hourly_rate' => $rate,
            'cost' => round($hours * $rate, 2),
        ]);

        // Recalculate budget if this entry is tied to a project with a budget
        if ($project && $project->budget) {
            $this->recalculate($project->budget);
        }

        // Also update the task's actual cost if a task is linked
        if ($entry->task_id) {
            $taskTotalCost = TimeEntry::where('task_id', $entry->task_id)->sum('cost');
            \App\Models\Task::where('id', $entry->task_id)
                ->update(['actual_cost' => $taskTotalCost]);
        }

        return $entry;
    }

    /**
     * Send a budget alert notification when spending reaches the threshold.
     */
    public function sendBudgetAlert(ProjectBudget $budget): void
    {
        $project = $budget->project;

        if (!$project || !auth()->check()) {
            return;
        }

        Notification::make()
            ->title('Budget Alert: ' . $project->name)
            ->body(
                number_format($budget->percentUsed(), 1) . '% of budget used. ' .
                '$' . number_format($budget->estimated_remaining, 2) . ' remaining.'
            )
            ->warning()
            ->persistent()
            ->sendToDatabase(auth()->user());
    }
}
