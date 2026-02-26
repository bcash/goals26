<?php

namespace App\Policies;

use App\Models\ConversationNote;
use App\Models\User;

class ConversationNotePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ConversationNote $conversationNote): bool
    {
        return $user->id === $conversationNote->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ConversationNote $conversationNote): bool
    {
        return $user->id === $conversationNote->user_id;
    }

    public function delete(User $user, ConversationNote $conversationNote): bool
    {
        return $user->id === $conversationNote->user_id;
    }

    public function restore(User $user, ConversationNote $conversationNote): bool
    {
        return $user->id === $conversationNote->user_id;
    }

    public function forceDelete(User $user, ConversationNote $conversationNote): bool
    {
        return $user->id === $conversationNote->user_id;
    }
}
