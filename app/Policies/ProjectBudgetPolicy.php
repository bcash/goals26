<?php

namespace App\Policies;

use App\Models\ProjectBudget;
use App\Models\User;

class ProjectBudgetPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ProjectBudget $projectBudget): bool
    {
        return $user->id === $projectBudget->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ProjectBudget $projectBudget): bool
    {
        return $user->id === $projectBudget->user_id;
    }

    public function delete(User $user, ProjectBudget $projectBudget): bool
    {
        return $user->id === $projectBudget->user_id;
    }

    public function restore(User $user, ProjectBudget $projectBudget): bool
    {
        return $user->id === $projectBudget->user_id;
    }

    public function forceDelete(User $user, ProjectBudget $projectBudget): bool
    {
        return $user->id === $projectBudget->user_id;
    }
}
