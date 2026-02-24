<?php

namespace App\Policies;

use App\Models\DeferredItem;
use App\Models\User;

class DeferredItemPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, DeferredItem $deferredItem): bool
    {
        return $user->id === $deferredItem->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, DeferredItem $deferredItem): bool
    {
        return $user->id === $deferredItem->user_id;
    }

    public function delete(User $user, DeferredItem $deferredItem): bool
    {
        return $user->id === $deferredItem->user_id;
    }

    public function restore(User $user, DeferredItem $deferredItem): bool
    {
        return $user->id === $deferredItem->user_id;
    }

    public function forceDelete(User $user, DeferredItem $deferredItem): bool
    {
        return $user->id === $deferredItem->user_id;
    }
}
