<?php

namespace App\Policies;

use App\Models\FreeScoutMailbox;
use App\Models\User;

class FreeScoutMailboxPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, FreeScoutMailbox $freeScoutMailbox): bool
    {
        return $user->id === $freeScoutMailbox->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, FreeScoutMailbox $freeScoutMailbox): bool
    {
        return $user->id === $freeScoutMailbox->user_id;
    }

    public function delete(User $user, FreeScoutMailbox $freeScoutMailbox): bool
    {
        return $user->id === $freeScoutMailbox->user_id;
    }

    public function restore(User $user, FreeScoutMailbox $freeScoutMailbox): bool
    {
        return $user->id === $freeScoutMailbox->user_id;
    }

    public function forceDelete(User $user, FreeScoutMailbox $freeScoutMailbox): bool
    {
        return $user->id === $freeScoutMailbox->user_id;
    }
}
