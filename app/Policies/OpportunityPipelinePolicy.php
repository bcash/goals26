<?php

namespace App\Policies;

use App\Models\OpportunityPipeline;
use App\Models\User;

class OpportunityPipelinePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, OpportunityPipeline $opportunityPipeline): bool
    {
        return $user->id === $opportunityPipeline->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, OpportunityPipeline $opportunityPipeline): bool
    {
        return $user->id === $opportunityPipeline->user_id;
    }

    public function delete(User $user, OpportunityPipeline $opportunityPipeline): bool
    {
        return $user->id === $opportunityPipeline->user_id;
    }

    public function restore(User $user, OpportunityPipeline $opportunityPipeline): bool
    {
        return $user->id === $opportunityPipeline->user_id;
    }

    public function forceDelete(User $user, OpportunityPipeline $opportunityPipeline): bool
    {
        return $user->id === $opportunityPipeline->user_id;
    }
}
