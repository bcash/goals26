<?php

namespace App\Services;

use App\Models\DeferralReview;
use App\Models\DeferredItem;
use App\Models\MeetingScopeItem;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DeferralService
{
    /**
     * Defer a task with full context.
     * Called when a task is moved to 'deferred' status during processing.
     */
    public function deferTask(
        Task $task,
        string $reason,
        ?string $note = null,
        ?Carbon $revisitDate = null,
        ?string $trigger = null,
        string $opportunityType = 'none',
        ?float $estimatedValue = null
    ): DeferredItem {
        // Update the task itself
        $task->update([
            'status' => 'deferred',
            'deferral_reason' => $reason,
            'deferral_note' => $note,
            'revisit_date' => $revisitDate,
            'deferral_trigger' => $trigger,
            'has_opportunity' => $opportunityType !== 'none',
        ]);

        // Create the deferred item record
        $item = DeferredItem::create([
            'user_id' => $task->user_id,
            'task_id' => $task->id,
            'project_id' => $task->project_id,
            'title' => $task->title,
            'description' => $task->notes,
            'deferral_reason' => $reason,
            'opportunity_type' => $opportunityType,
            'estimated_value' => $estimatedValue,
            'status' => $revisitDate ? 'scheduled' : 'someday',
            'deferred_on' => today(),
            'revisit_date' => $revisitDate,
            'revisit_trigger' => $trigger,
            'client_name' => $task->project?->client_name,
            'why_it_matters' => $note,
        ]);

        // Auto-generate AI opportunity analysis if commercial value detected
        if ($opportunityType !== 'none' && $opportunityType !== 'personal-goal') {
            $this->queueOpportunityAnalysis($item);
        }

        return $item;
    }

    /**
     * Capture a deferred item directly from a scope item in a client meeting.
     * Out-of-scope and deferred items from meeting transcripts flow here.
     */
    public function captureFromScopeItem(
        MeetingScopeItem $scopeItem,
        string $opportunityType = 'phase-2',
        ?string $revisitDate = null
    ): DeferredItem {
        $project = $scopeItem->meeting->project;

        return DeferredItem::create([
            'user_id' => auth()->id(),
            'meeting_id' => $scopeItem->meeting_id,
            'scope_item_id' => $scopeItem->id,
            'project_id' => $project?->id,
            'title' => $scopeItem->description,
            'client_context' => $scopeItem->client_quote,
            'client_name' => $project?->client_name,
            'client_quote' => $scopeItem->client_quote,
            'deferral_reason' => $this->mapScopeTypeToDeferralReason($scopeItem->type),
            'opportunity_type' => $opportunityType,
            'status' => $revisitDate ? 'scheduled' : 'someday',
            'deferred_on' => today(),
            'revisit_date' => $revisitDate,
        ]);
    }

    /**
     * Capture a freeform idea for the Someday/Maybe list.
     * Quick capture during a brainstorm, meeting, or daily review.
     */
    public function captureIdea(array $data): DeferredItem
    {
        return DeferredItem::create([
            'user_id' => auth()->id(),
            'project_id' => $data['project_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'client_name' => $data['client_name'] ?? null,
            'opportunity_type' => $data['opportunity_type'] ?? 'none',
            'deferral_reason' => $data['deferral_reason'] ?? 'priority',
            'status' => 'someday',
            'deferred_on' => today(),
        ]);
    }

    /**
     * Capture a personal goal with resource requirements.
     * Personal goals are deferred items where the client is yourself.
     */
    public function capturePersonalGoal(array $data): DeferredItem
    {
        $resourceRequirements = $data['resource_requirements'] ?? null;
        $deferralReason = $this->detectDeferralReason($resourceRequirements ?? []);

        return DeferredItem::create([
            'user_id' => auth()->id(),
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'client_type' => 'self',
            'opportunity_type' => $data['opportunity_type'] ?? 'personal-goal',
            'deferral_reason' => $data['deferral_reason'] ?? $deferralReason,
            'resource_requirements' => $resourceRequirements,
            'resource_check_done' => false,
            'status' => isset($data['revisit_date']) ? 'scheduled' : 'someday',
            'deferred_on' => today(),
            'revisit_date' => $data['revisit_date'] ?? null,
            'revisit_trigger' => $data['revisit_trigger'] ?? null,
            'why_it_matters' => $data['why_it_matters'] ?? null,
        ]);
    }

    /**
     * Process a weekly review of the Someday/Maybe list.
     * Returns items due for review, grouped by category.
     */
    public function getWeeklyReviewItems(\App\Models\User $user): Collection
    {
        $items = DeferredItem::dueForReview()
            ->orderBy('estimated_value', 'desc')
            ->orderBy('deferred_on')
            ->get();

        return collect([
            'overdue' => $items->filter(fn ($i) => $i->isOverdue()),
            'scheduled' => $items->where('status', 'scheduled')
                ->filter(fn ($i) => $i->revisit_date && $i->revisit_date->lte(today()->addDays(7))),
            'someday' => $items->where('status', 'someday')
                ->where('opportunity_type', '!=', 'none')
                ->take(10),
            'commercial' => $items->filter(fn ($i) =>
                !in_array($i->opportunity_type, ['none', 'personal-goal'])
                && in_array($i->status, ['someday', 'scheduled', 'in-review', 'promoted'])
            ),
        ]);
    }

    /**
     * Submit a deferral review -- log outcome and update item.
     */
    public function submitReview(
        DeferredItem $item,
        string $decision,
        ?string $notes = null,
        ?string $nextRevisitDate = null
    ): void {
        DeferralReview::create([
            'user_id' => $item->user_id,
            'deferred_item_id' => $item->id,
            'reviewed_on' => today(),
            'outcome' => $decision,
            'next_revisit_date' => $nextRevisitDate,
            'review_notes' => $notes,
        ]);

        $item->increment('review_count');
        $item->update(['last_reviewed_at' => now()]);

        match ($decision) {
            'keep-someday' => $item->update(['status' => 'someday', 'revisit_date' => null]),
            'reschedule' => $item->update(['status' => 'scheduled', 'revisit_date' => $nextRevisitDate]),
            'promote' => $item->promote(),
            'propose' => $this->flagForProposal($item),
            'archive' => $item->update(['status' => 'archived']),
            default => null,
        };
    }

    // -- Private Helpers --

    private function mapScopeTypeToDeferralReason(string $scopeType): string
    {
        return match ($scopeType) {
            'out-of-scope' => 'scope-control',
            'deferred' => 'timeline',
            'assumption' => 'awaiting-decision',
            'risk' => 'priority',
            default => 'priority',
        };
    }

    private function detectDeferralReason(array $resourceRequirements): string
    {
        if (isset($resourceRequirements['money']) && $resourceRequirements['money'] > 0) {
            return 'budget';
        }

        if (isset($resourceRequirements['time']) && $resourceRequirements['time'] > 20) {
            return 'timeline';
        }

        if (isset($resourceRequirements['capability'])) {
            return 'client-not-ready';
        }

        if (isset($resourceRequirements['technology'])) {
            return 'technology';
        }

        if (isset($resourceRequirements['energy']) && in_array($resourceRequirements['energy'], ['high', 'maximum'])) {
            return 'priority';
        }

        if (isset($resourceRequirements['readiness']) && $resourceRequirements['readiness'] === false) {
            return 'awaiting-decision';
        }

        if (isset($resourceRequirements['dependency'])) {
            return 'scope-control';
        }

        return 'priority';
    }

    private function flagForProposal(DeferredItem $item): void
    {
        if (!$item->opportunity) {
            $item->promote();
        }

        $item->refresh();

        if ($item->opportunity) {
            $item->opportunity->update(['stage' => 'proposing']);
        }

        $item->update(['status' => 'promoted']);
    }

    private function queueOpportunityAnalysis(DeferredItem $item): void
    {
        // Dispatched as a queued job to avoid slowing down the UI
        if (class_exists(\App\Jobs\AnalyzeDeferredOpportunity::class)) {
            \App\Jobs\AnalyzeDeferredOpportunity::dispatch($item);
        }
    }
}
