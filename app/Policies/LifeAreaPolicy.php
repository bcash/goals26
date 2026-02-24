<?php

namespace App\Policies;

use App\Models\LifeArea;
use App\Models\User;

class LifeAreaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, LifeArea $lifeArea): bool
    {
        return $user->id === $lifeArea->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, LifeArea $lifeArea): bool
    {
        return $user->id === $lifeArea->user_id;
    }

    public function delete(User $user, LifeArea $lifeArea): bool
    {
        return $user->id === $lifeArea->user_id;
    }

    public function restore(User $user, LifeArea $lifeArea): bool
    {
        return $user->id === $lifeArea->user_id;
    }

    public function forceDelete(User $user, LifeArea $lifeArea): bool
    {
        return $user->id === $lifeArea->user_id;
    }
}
