<?php

namespace App\Policies;

use App\Models\MeetingAgenda;
use App\Models\User;

class MeetingAgendaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MeetingAgenda $meetingAgenda): bool
    {
        return $user->id === $meetingAgenda->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MeetingAgenda $meetingAgenda): bool
    {
        return $user->id === $meetingAgenda->user_id;
    }

    public function delete(User $user, MeetingAgenda $meetingAgenda): bool
    {
        return $user->id === $meetingAgenda->user_id;
    }

    public function restore(User $user, MeetingAgenda $meetingAgenda): bool
    {
        return $user->id === $meetingAgenda->user_id;
    }

    public function forceDelete(User $user, MeetingAgenda $meetingAgenda): bool
    {
        return $user->id === $meetingAgenda->user_id;
    }
}
