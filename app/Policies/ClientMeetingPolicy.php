<?php

namespace App\Policies;

use App\Models\ClientMeeting;
use App\Models\User;

class ClientMeetingPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ClientMeeting $clientMeeting): bool
    {
        return $user->id === $clientMeeting->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ClientMeeting $clientMeeting): bool
    {
        return $user->id === $clientMeeting->user_id;
    }

    public function delete(User $user, ClientMeeting $clientMeeting): bool
    {
        return $user->id === $clientMeeting->user_id;
    }

    public function restore(User $user, ClientMeeting $clientMeeting): bool
    {
        return $user->id === $clientMeeting->user_id;
    }

    public function forceDelete(User $user, ClientMeeting $clientMeeting): bool
    {
        return $user->id === $clientMeeting->user_id;
    }
}
