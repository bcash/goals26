<?php

namespace App\Policies;

use App\Models\MeetingResourceSignal;
use App\Models\User;

class MeetingResourceSignalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MeetingResourceSignal $meetingResourceSignal): bool
    {
        return $user->id === $meetingResourceSignal->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MeetingResourceSignal $meetingResourceSignal): bool
    {
        return $user->id === $meetingResourceSignal->user_id;
    }

    public function delete(User $user, MeetingResourceSignal $meetingResourceSignal): bool
    {
        return $user->id === $meetingResourceSignal->user_id;
    }

    public function restore(User $user, MeetingResourceSignal $meetingResourceSignal): bool
    {
        return $user->id === $meetingResourceSignal->user_id;
    }

    public function forceDelete(User $user, MeetingResourceSignal $meetingResourceSignal): bool
    {
        return $user->id === $meetingResourceSignal->user_id;
    }
}
