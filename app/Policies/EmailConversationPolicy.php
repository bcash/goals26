<?php

namespace App\Policies;

use App\Models\EmailConversation;
use App\Models\User;

class EmailConversationPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, EmailConversation $emailConversation): bool
    {
        return $user->id === $emailConversation->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, EmailConversation $emailConversation): bool
    {
        return $user->id === $emailConversation->user_id;
    }

    public function delete(User $user, EmailConversation $emailConversation): bool
    {
        return $user->id === $emailConversation->user_id;
    }

    public function restore(User $user, EmailConversation $emailConversation): bool
    {
        return $user->id === $emailConversation->user_id;
    }

    public function forceDelete(User $user, EmailConversation $emailConversation): bool
    {
        return $user->id === $emailConversation->user_id;
    }
}
