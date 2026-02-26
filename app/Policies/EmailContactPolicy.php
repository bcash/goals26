<?php

namespace App\Policies;

use App\Models\EmailContact;
use App\Models\User;

class EmailContactPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, EmailContact $emailContact): bool
    {
        return $user->id === $emailContact->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, EmailContact $emailContact): bool
    {
        return $user->id === $emailContact->user_id;
    }

    public function delete(User $user, EmailContact $emailContact): bool
    {
        return $user->id === $emailContact->user_id;
    }

    public function restore(User $user, EmailContact $emailContact): bool
    {
        return $user->id === $emailContact->user_id;
    }

    public function forceDelete(User $user, EmailContact $emailContact): bool
    {
        return $user->id === $emailContact->user_id;
    }
}
