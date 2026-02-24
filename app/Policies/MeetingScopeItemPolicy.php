<?php

namespace App\Policies;

use App\Models\MeetingScopeItem;
use App\Models\User;

class MeetingScopeItemPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MeetingScopeItem $meetingScopeItem): bool
    {
        return $user->id === $meetingScopeItem->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MeetingScopeItem $meetingScopeItem): bool
    {
        return $user->id === $meetingScopeItem->user_id;
    }

    public function delete(User $user, MeetingScopeItem $meetingScopeItem): bool
    {
        return $user->id === $meetingScopeItem->user_id;
    }

    public function restore(User $user, MeetingScopeItem $meetingScopeItem): bool
    {
        return $user->id === $meetingScopeItem->user_id;
    }

    public function forceDelete(User $user, MeetingScopeItem $meetingScopeItem): bool
    {
        return $user->id === $meetingScopeItem->user_id;
    }
}
