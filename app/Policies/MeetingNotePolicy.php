<?php

namespace App\Policies;

use App\Models\MeetingNote;
use App\Models\User;

class MeetingNotePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MeetingNote $meetingNote): bool
    {
        return $user->id === $meetingNote->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MeetingNote $meetingNote): bool
    {
        return $user->id === $meetingNote->user_id;
    }

    public function delete(User $user, MeetingNote $meetingNote): bool
    {
        return $user->id === $meetingNote->user_id;
    }

    public function restore(User $user, MeetingNote $meetingNote): bool
    {
        return $user->id === $meetingNote->user_id;
    }

    public function forceDelete(User $user, MeetingNote $meetingNote): bool
    {
        return $user->id === $meetingNote->user_id;
    }
}
