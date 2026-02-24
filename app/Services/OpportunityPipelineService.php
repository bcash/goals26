<?php

namespace App\Services;

use App\Models\DeferredItem;
use App\Models\LifeArea;
use App\Models\OpportunityPipeline;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class OpportunityPipelineService
{
    /**
     * Get the full pipeline summary -- total value, weighted value,
     * items by stage, and next actions due this week.
     */
    public function getSummary(User $user): array
    {
        $opportunities = OpportunityPipeline::whereNotIn('stage', ['closed-won', 'closed-lost'])
            ->with('deferredItem')
            ->get();

        $totalValue = $opportunities->sum('estimated_value');
        $weightedValue = $opportunities->sum(fn ($o) => $o->weightedValue());

        $byStage = $opportunities->groupBy('stage')
            ->map(fn ($group) => [
                'count' => $group->count(),
                'value' => $group->sum('estimated_value'),
                'weighted' => $group->sum(fn ($o) => $o->weightedValue()),
            ]);

        $actionsThisWeek = $opportunities
            ->filter(fn ($o) => $o->next_action_date !== null)
            ->filter(fn ($o) => $o->next_action_date->lte(today()->addDays(7)))
            ->sortBy('next_action_date');

        return compact('totalValue', 'weightedValue', 'byStage', 'actionsThisWeek');
    }

    /**
     * Advance an opportunity to the next pipeline stage.
     */
    public function advanceStage(OpportunityPipeline $opportunity): void
    {
        $stages = [
            'identified',
            'qualifying',
            'nurturing',
            'proposing',
            'negotiating',
            'closed-won',
        ];

        $currentIndex = array_search($opportunity->stage, $stages);

        if ($currentIndex !== false && isset($stages[$currentIndex + 1])) {
            $nextStage = $stages[$currentIndex + 1];

            // Increase probability as stage advances
            $probabilities = [
                'identified' => 20,
                'qualifying' => 30,
                'nurturing' => 40,
                'proposing' => 60,
                'negotiating' => 80,
                'closed-won' => 100,
            ];

            $opportunity->update([
                'stage' => $nextStage,
                'probability_percent' => $probabilities[$nextStage] ?? $opportunity->probability_percent,
            ]);
        }
    }

    /**
     * Close an opportunity as won.
     * Optionally creates a new Project from the opportunity details.
     */
    public function closeWon(
        OpportunityPipeline $opportunity,
        float $actualValue,
        bool $createProject = true
    ): ?int {
        $opportunity->update([
            'stage' => 'closed-won',
            'actual_value' => $actualValue,
            'actual_close_date' => today(),
            'probability_percent' => 100,
        ]);

        $opportunity->deferredItem->update(['status' => 'won']);

        if ($createProject) {
            $lifeAreaId = $opportunity->deferredItem?->task?->life_area_id
                ?? LifeArea::where('name', 'Business')->value('id')
                ?? LifeArea::first()?->id;

            $project = Project::create([
                'user_id' => $opportunity->user_id,
                'life_area_id' => $lifeAreaId,
                'name' => $opportunity->title,
                'description' => $opportunity->description,
                'client_name' => $opportunity->client_name,
                'status' => 'active',
            ]);

            $opportunity->update(['project_id' => $project->id]);

            return $project->id;
        }

        return null;
    }

    /**
     * Get all items in the Someday/Maybe list that have commercial value
     * and have not been reviewed recently.
     */
    public function getStaleHighValueItems(User $user, int $daysSinceUpdate = 14): EloquentCollection
    {
        return DeferredItem::hasCommercialValue()
            ->where(fn ($q) => $q->whereNull('last_reviewed_at')
                ->orWhere('last_reviewed_at', '<', now()->subDays($daysSinceUpdate))
            )
            ->orderByDesc('estimated_value')
            ->limit(10)
            ->get();
    }

    /**
     * Calculate the total weighted pipeline value across all open opportunities.
     */
    public function totalWeightedPipeline(User $user): float
    {
        return OpportunityPipeline::whereNotIn('stage', ['closed-won', 'closed-lost'])
            ->get()
            ->sum(fn ($o) => $o->weightedValue());
    }
}
