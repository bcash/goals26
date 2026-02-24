<?php

namespace App\Policies;

use App\Models\AiInteraction;
use App\Models\User;

class AiInteractionPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, AiInteraction $aiInteraction): bool
    {
        return $user->id === $aiInteraction->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, AiInteraction $aiInteraction): bool
    {
        return $user->id === $aiInteraction->user_id;
    }

    public function delete(User $user, AiInteraction $aiInteraction): bool
    {
        return $user->id === $aiInteraction->user_id;
    }

    public function restore(User $user, AiInteraction $aiInteraction): bool
    {
        return $user->id === $aiInteraction->user_id;
    }

    public function forceDelete(User $user, AiInteraction $aiInteraction): bool
    {
        return $user->id === $aiInteraction->user_id;
    }
}
