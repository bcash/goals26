<?php

namespace App\Policies;

use App\Models\DeferralReview;
use App\Models\User;

class DeferralReviewPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DeferralReview $deferralReview): bool
    {
        return $user->id === $deferralReview->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, DeferralReview $deferralReview): bool
    {
        return $user->id === $deferralReview->user_id;
    }

    public function delete(User $user, DeferralReview $deferralReview): bool
    {
        return $user->id === $deferralReview->user_id;
    }

    public function restore(User $user, DeferralReview $deferralReview): bool
    {
        return $user->id === $deferralReview->user_id;
    }

    public function forceDelete(User $user, DeferralReview $deferralReview): bool
    {
        return $user->id === $deferralReview->user_id;
    }
}
