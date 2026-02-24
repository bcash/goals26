<?php

namespace App\Policies;

use App\Models\MeetingDoneItem;
use App\Models\User;

class MeetingDoneItemPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MeetingDoneItem $meetingDoneItem): bool
    {
        return $user->id === $meetingDoneItem->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MeetingDoneItem $meetingDoneItem): bool
    {
        return $user->id === $meetingDoneItem->user_id;
    }

    public function delete(User $user, MeetingDoneItem $meetingDoneItem): bool
    {
        return $user->id === $meetingDoneItem->user_id;
    }

    public function restore(User $user, MeetingDoneItem $meetingDoneItem): bool
    {
        return $user->id === $meetingDoneItem->user_id;
    }

    public function forceDelete(User $user, MeetingDoneItem $meetingDoneItem): bool
    {
        return $user->id === $meetingDoneItem->user_id;
    }
}
