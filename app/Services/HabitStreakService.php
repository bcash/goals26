<?php

namespace App\Services;

use App\Models\Habit;
use App\Models\HabitLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HabitStreakService
{
    /**
     * Calculate the current streak for a habit.
     * A streak is the number of consecutive days (or target days) the habit was completed.
     */
    public function calculateStreak(Habit $habit): int
    {
        $logs = $habit->logs()
            ->where('status', 'completed')
            ->orderByDesc('logged_date')
            ->pluck('logged_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString());

        if ($logs->isEmpty()) {
            return 0;
        }

        $streak = 0;
        $checkDate = today();

        // If today is not yet logged, start from yesterday
        if (!$logs->contains($checkDate->toDateString())) {
            $checkDate = today()->subDay();
        }

        // For daily habits, check consecutive calendar days
        if (in_array($habit->frequency, ['daily', 'weekdays'])) {
            while ($logs->contains($checkDate->toDateString())) {
                // For weekday habits, skip weekends
                if ($habit->frequency === 'weekdays' && $checkDate->isWeekend()) {
                    $checkDate->subDay();
                    continue;
                }

                $streak++;
                $checkDate->subDay();

                // Skip weekends for weekday habits
                if ($habit->frequency === 'weekdays') {
                    while ($checkDate->isWeekend()) {
                        $checkDate->subDay();
                    }
                }
            }
        } elseif ($habit->frequency === 'weekly') {
            // For weekly habits, check once per 7-day period
            while ($logs->contains($checkDate->toDateString())) {
                $streak++;
                $checkDate->subWeek();
            }
        } elseif ($habit->frequency === 'custom' && !empty($habit->target_days)) {
            // For custom frequency, check only target days
            $targetDays = $habit->target_days;

            while (true) {
                // Skip non-target days
                if (!in_array($checkDate->dayOfWeek, $targetDays)) {
                    $checkDate->subDay();
                    continue;
                }

                if ($logs->contains($checkDate->toDateString())) {
                    $streak++;
                    $checkDate->subDay();
                } else {
                    break;
                }
            }
        }

        // Update the habit's streak fields
        $habit->update([
            'streak_current' => $streak,
            'streak_best' => max($streak, $habit->streak_best),
        ]);

        return $streak;
    }

    /**
     * Recalculate streak (alias for calculateStreak).
     */
    public function recalculate(Habit $habit): int
    {
        return $this->calculateStreak($habit);
    }

    /**
     * Log today's completion for a habit.
     */
    public function logToday(Habit $habit): HabitLog
    {
        $log = HabitLog::updateOrCreate(
            [
                'habit_id' => $habit->id,
                'logged_date' => today()->toDateString(),
            ],
            [
                'status' => 'completed',
            ]
        );

        $this->calculateStreak($habit);

        return $log;
    }

    /**
     * Check if a habit has been completed today.
     */
    public function isCompletedToday(Habit $habit): bool
    {
        return $habit->logs()
            ->where('logged_date', today()->toDateString())
            ->where('status', 'completed')
            ->exists();
    }

    /**
     * Get the completion rate for a habit over a given number of days.
     * Returns a float between 0.0 and 1.0.
     */
    public function getCompletionRate(Habit $habit, int $days = 30): float
    {
        $startDate = today()->subDays($days);

        $completedDays = $habit->logs()
            ->where('status', 'completed')
            ->where('logged_date', '>=', $startDate->toDateString())
            ->count();

        // Calculate eligible days based on frequency
        $eligibleDays = $this->countEligibleDays($habit, $startDate, today());

        if ($eligibleDays === 0) {
            return 0.0;
        }

        return round($completedDays / $eligibleDays, 4);
    }

    /**
     * Get all active streaks for a user, sorted by streak length.
     */
    public function getActiveStreaks(User $user): Collection
    {
        return $user->habits()
            ->where('status', 'active')
            ->where('streak_current', '>', 0)
            ->orderByDesc('streak_current')
            ->get()
            ->map(fn ($habit) => [
                'habit' => $habit,
                'streak' => $habit->streak_current,
                'best' => $habit->streak_best,
                'is_personal_best' => $habit->streak_current >= $habit->streak_best,
                'life_area' => $habit->lifeArea?->name,
                'color' => $habit->lifeArea?->color_hex,
            ]);
    }

    /**
     * Count the number of days a habit was eligible to be completed
     * between two dates (accounting for frequency and target days).
     */
    private function countEligibleDays(Habit $habit, Carbon $start, Carbon $end): int
    {
        $days = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if ($this->isDayEligible($habit, $current)) {
                $days++;
            }
            $current->addDay();
        }

        return $days;
    }

    /**
     * Check if a specific date is an eligible day for this habit.
     */
    private function isDayEligible(Habit $habit, Carbon $date): bool
    {
        // Don't count days before the habit started
        if ($habit->started_at && $date->lt($habit->started_at)) {
            return false;
        }

        return match ($habit->frequency) {
            'daily' => true,
            'weekdays' => !$date->isWeekend(),
            'weekly' => $date->dayOfWeek === ($habit->target_days[0] ?? 1),
            'custom' => in_array($date->dayOfWeek, $habit->target_days ?? []),
            default => true,
        };
    }
}
