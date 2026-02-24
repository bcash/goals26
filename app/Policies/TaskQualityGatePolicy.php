<?php

namespace App\Policies;

use App\Models\TaskQualityGate;
use App\Models\User;

class TaskQualityGatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TaskQualityGate $taskQualityGate): bool
    {
        return $user->id === $taskQualityGate->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, TaskQualityGate $taskQualityGate): bool
    {
        return $user->id === $taskQualityGate->user_id;
    }

    public function delete(User $user, TaskQualityGate $taskQualityGate): bool
    {
        return $user->id === $taskQualityGate->user_id;
    }

    public function restore(User $user, TaskQualityGate $taskQualityGate): bool
    {
        return $user->id === $taskQualityGate->user_id;
    }

    public function forceDelete(User $user, TaskQualityGate $taskQualityGate): bool
    {
        return $user->id === $taskQualityGate->user_id;
    }
}
