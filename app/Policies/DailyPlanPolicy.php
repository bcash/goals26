<?php

namespace App\Policies;

use App\Models\DailyPlan;
use App\Models\User;

class DailyPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DailyPlan $dailyPlan): bool
    {
        return $user->id === $dailyPlan->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, DailyPlan $dailyPlan): bool
    {
        return $user->id === $dailyPlan->user_id;
    }

    public function delete(User $user, DailyPlan $dailyPlan): bool
    {
        return $user->id === $dailyPlan->user_id;
    }

    public function restore(User $user, DailyPlan $dailyPlan): bool
    {
        return $user->id === $dailyPlan->user_id;
    }

    public function forceDelete(User $user, DailyPlan $dailyPlan): bool
    {
        return $user->id === $dailyPlan->user_id;
    }
}
