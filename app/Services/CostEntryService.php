<?php

namespace App\Services;

use App\Models\CostEntry;
use App\Models\Project;
use App\Models\Task;
use Money\Currency;
use Money\Money;

class CostEntryService
{
    /**
     * Create a new cost entry.
     */
    public function create(array $data): CostEntry
    {
        return CostEntry::create($data);
    }

    /**
     * Total cost for a project, optionally filtered by category and/or billable status.
     */
    public function totalForProject(Project $project, ?string $category = null, ?bool $billable = null): Money
    {
        $query = CostEntry::where('project_id', $project->id);

        if ($category !== null) {
            $query->where('category', $category);
        }

        if ($billable !== null) {
            $query->where('billable', $billable);
        }

        $cents = (int) $query->sum('amount_cents');
        $currency = $project->budget_currency ?? 'USD';

        return new Money($cents, new Currency($currency));
    }

    /**
     * Total cost for a task, optionally filtered by category.
     */
    public function totalForTask(Task $task, ?string $category = null): Money
    {
        $query = CostEntry::where('task_id', $task->id);

        if ($category !== null) {
            $query->where('category', $category);
        }

        $cents = (int) $query->sum('amount_cents');

        return new Money($cents, new Currency('USD'));
    }

    /**
     * Total duration in minutes for all cost entries on a task.
     */
    public function totalMinutesForTask(Task $task): int
    {
        return (int) CostEntry::where('task_id', $task->id)->sum('duration_minutes');
    }

    /**
     * Remaining budget for a project (budget_cents minus billable costs).
     */
    public function budgetRemaining(Project $project): Money
    {
        $currency = new Currency($project->budget_currency ?? 'USD');
        $budget = new Money($project->getRawOriginal('budget_cents') ?? 0, $currency);
        $spent = $this->totalForProject($project, billable: true);

        return $budget->subtract($spent);
    }

    /**
     * Budget utilization as a percentage (0-100+).
     */
    public function budgetUtilization(Project $project): float
    {
        $budgetCents = $project->getRawOriginal('budget_cents');

        if (!$budgetCents || $budgetCents <= 0) {
            return 0.0;
        }

        $spentCents = (int) CostEntry::where('project_id', $project->id)
            ->where('billable', true)
            ->sum('amount_cents');

        return round(($spentCents / $budgetCents) * 100, 1);
    }

    /**
     * Whether a project's billable costs exceed its budget.
     */
    public function isOverBudget(Project $project): bool
    {
        $budgetCents = $project->getRawOriginal('budget_cents');

        if (!$budgetCents || $budgetCents <= 0) {
            return false;
        }

        $spentCents = (int) CostEntry::where('project_id', $project->id)
            ->where('billable', true)
            ->sum('amount_cents');

        return $spentCents > $budgetCents;
    }
}
