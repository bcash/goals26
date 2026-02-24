<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

class GoalProgressService
{
    /**
     * Recalculate the progress percentage for a goal.
     * Progress is based on the completion rate of linked tasks
     * and milestones.
     */
    public function recalculate(Goal $goal): void
    {
        $totalTasks = $goal->tasks()->count();

        if ($totalTasks === 0) {
            // If there are milestones, use milestone completion
            $totalMilestones = $goal->milestones()->count();

            if ($totalMilestones > 0) {
                $completeMilestones = $goal->milestones()
                    ->where('status', 'complete')
                    ->count();

                $progress = (int) round(($completeMilestones / $totalMilestones) * 100);
            } else {
                $progress = 0;
            }
        } else {
            $doneTasks = $goal->tasks()->where('status', 'done')->count();
            $progress = (int) round(($doneTasks / $totalTasks) * 100);
        }

        $goal->update(['progress_percent' => min(100, $progress)]);

        // Auto-update status if 100% complete
        if ($progress >= 100 && $goal->status === 'active') {
            $goal->update(['status' => 'achieved']);
        }
    }

    /**
     * Get goal progress grouped by life area.
     * Returns a collection keyed by life area name, each containing
     * the goals in that area with their progress.
     */
    public function getByLifeArea(User $user): Collection
    {
        return $user->goals()
            ->with('lifeArea')
            ->where('status', 'active')
            ->get()
            ->groupBy(fn ($goal) => $goal->lifeArea?->name ?? 'Uncategorized')
            ->map(fn ($goals, $areaName) => [
                'area_name' => $areaName,
                'color' => $goals->first()->lifeArea?->color_hex ?? '#C9A84C',
                'icon' => $goals->first()->lifeArea?->icon ?? '',
                'goals' => $goals->map(fn ($goal) => [
                    'id' => $goal->id,
                    'title' => $goal->title,
                    'progress' => $goal->progress_percent,
                    'horizon' => $goal->horizon,
                    'target_date' => $goal->target_date?->format('M j, Y'),
                    'status' => $goal->status,
                ]),
                'average_progress' => (int) round($goals->avg('progress_percent')),
                'goal_count' => $goals->count(),
            ]);
    }

    /**
     * Get all goals that are on track (progress is within expected range
     * based on time elapsed vs target date).
     */
    public function getOnTrack(User $user): Collection
    {
        return $user->goals()
            ->where('status', 'active')
            ->with('lifeArea')
            ->get()
            ->filter(function ($goal) {
                $expectedProgress = $this->calculateExpectedProgress($goal);
                // On track if actual progress >= 80% of expected progress
                return $goal->progress_percent >= ($expectedProgress * 0.8);
            })
            ->values();
    }

    /**
     * Get all goals that are at risk (progress is significantly behind
     * the expected rate based on time elapsed).
     */
    public function getAtRisk(User $user): Collection
    {
        return $user->goals()
            ->where('status', 'active')
            ->with('lifeArea')
            ->get()
            ->filter(function ($goal) {
                $expectedProgress = $this->calculateExpectedProgress($goal);
                // At risk if actual progress is less than 60% of expected progress
                return $goal->progress_percent < ($expectedProgress * 0.6)
                    && $expectedProgress > 0;
            })
            ->values();
    }

    /**
     * Calculate the expected progress percentage based on time elapsed.
     * If no target date is set, returns 50 as a baseline expectation.
     */
    private function calculateExpectedProgress(Goal $goal): float
    {
        if (!$goal->target_date) {
            // Without a target date, use horizon to estimate
            return match ($goal->horizon) {
                '90-day' => $this->estimateProgressByHorizon($goal, 90),
                '1-year' => $this->estimateProgressByHorizon($goal, 365),
                '3-year' => $this->estimateProgressByHorizon($goal, 1095),
                'lifetime' => 10.0, // Lifetime goals have a very low expected rate
                default => 50.0,
            };
        }

        $totalDays = $goal->created_at->diffInDays($goal->target_date);

        if ($totalDays <= 0) {
            return 100.0;
        }

        $elapsedDays = $goal->created_at->diffInDays(now());
        $progress = ($elapsedDays / $totalDays) * 100;

        return min(100.0, round($progress, 1));
    }

    /**
     * Estimate expected progress based on the horizon duration.
     */
    private function estimateProgressByHorizon(Goal $goal, int $horizonDays): float
    {
        $daysSinceCreated = $goal->created_at->diffInDays(now());

        if ($horizonDays <= 0) {
            return 100.0;
        }

        $progress = ($daysSinceCreated / $horizonDays) * 100;

        return min(100.0, round($progress, 1));
    }
}
