<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WeeklyReview;

class WeeklyReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WeeklyReview $weeklyReview): bool
    {
        return $user->id === $weeklyReview->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, WeeklyReview $weeklyReview): bool
    {
        return $user->id === $weeklyReview->user_id;
    }

    public function delete(User $user, WeeklyReview $weeklyReview): bool
    {
        return $user->id === $weeklyReview->user_id;
    }

    public function restore(User $user, WeeklyReview $weeklyReview): bool
    {
        return $user->id === $weeklyReview->user_id;
    }

    public function forceDelete(User $user, WeeklyReview $weeklyReview): bool
    {
        return $user->id === $weeklyReview->user_id;
    }
}
