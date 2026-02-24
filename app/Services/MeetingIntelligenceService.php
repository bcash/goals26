<?php

namespace App\Services;

use App\Models\ClientMeeting;
use App\Models\DeferredItem;
use App\Models\LifeArea;
use App\Models\MeetingDoneItem;
use App\Models\MeetingResourceSignal;
use App\Models\MeetingScopeItem;
use App\Models\Task;
use Filament\Notifications\Notification;

class MeetingIntelligenceService
{
    public function __construct(protected AiService $ai) {}

    /**
     * Full analysis pipeline for a meeting transcript.
     * Extracts all intelligence categories and persists them.
     */
    public function analyze(ClientMeeting $meeting): void
    {
        $meeting->update(['transcription_status' => 'processing']);

        try {
            $extracted = $this->extractAll($meeting);

            $this->persistDoneItems($meeting, $extracted['done_items'] ?? []);
            $this->persistScopeItems($meeting, $extracted['scope_items'] ?? []);
            $this->persistDeferredItems($meeting, $extracted['deferred_items'] ?? []);
            $this->persistResourceSignals($meeting, $extracted['resource_signals'] ?? []);
            $this->persistActionItems($meeting, $extracted['action_items'] ?? []);

            $meeting->update([
                'summary' => $extracted['summary'] ?? null,
                'decisions' => $extracted['decisions'] ?? null,
                'ai_scope_analysis' => $extracted['scope_analysis'] ?? null,
                'transcription_status' => 'complete',
                'analysis_completed_at' => now(),
            ]);

            $this->notifyUser($meeting, $extracted);

        } catch (\Exception $e) {
            $meeting->update(['transcription_status' => 'failed']);
            throw $e;
        }
    }

    /**
     * Call AI once with a comprehensive extraction prompt.
     * Returns all categories in a single structured JSON response.
     */
    public function extractAll(ClientMeeting $meeting): array
    {
        $clientContext = $meeting->isSelfMeeting()
            ? "This is an internal planning session. The 'client' is the user themselves."
            : "This is a meeting with an external client: {$meeting->project?->client_name}.";

        $prompt = <<<PROMPT
You are a project intelligence analyst. Analyze this meeting transcript and extract structured data.

MEETING: "{$meeting->title}"
DATE: {$meeting->meeting_date->format('M j, Y')}
TYPE: {$meeting->meeting_type}
PROJECT: {$meeting->project?->name ?? 'No linked project'}
CONTEXT: {$clientContext}

TRANSCRIPT:
{$meeting->transcript}

Extract the following in a single JSON response:

{
  "summary": "3-4 sentence meeting summary",

  "decisions": "Bulleted list of explicit decisions made",

  "done_items": [
    {
      "title": "What was confirmed as complete",
      "outcome": "What impact or result this had",
      "outcome_metric": "Quantified result if mentioned (e.g. '18% improvement')",
      "client_quote": "Exact words if the client acknowledged the work",
      "value_delivered": null or number,
      "save_as_testimonial": true if the quote is praise-worthy
    }
  ],

  "action_items": [
    {
      "title": "Next step",
      "assigned_to": "client | user | both",
      "due": "date string or null",
      "priority": "high | medium | low"
    }
  ],

  "scope_items": [
    {
      "description": "What was discussed",
      "type": "in-scope | out-of-scope | deferred | assumption | risk",
      "client_quote": "Exact words if available",
      "confirmed_with_client": true or false
    }
  ],

  "deferred_items": [
    {
      "title": "What was set aside",
      "reason": "budget | timeline | priority | client-not-ready | scope-control | awaiting-decision | technology | personal",
      "opportunity_type": "phase-2 | upsell | upgrade | new-project | retainer | product-feature | personal-goal | none",
      "client_quote": "Exact words explaining the deferral",
      "estimated_value": null or number,
      "revisit_trigger": "What would bring this back to the table",
      "why_it_matters": "Why this is worth keeping"
    }
  ],

  "resource_signals": [
    {
      "resource_type": "budget | time | technology | capability | team | readiness | dependency",
      "description": "What constraint was mentioned",
      "client_quote": "Exact words",
      "constraint_timeline": "When this constraint might lift"
    }
  ],

  "scope_analysis": "A paragraph summary of what is and isn't included in scope, and any risks to budget or timeline"
}

Be conservative with dollar estimates. Only include them if explicitly discussed or strongly implied.
For internal/self meetings, treat 'client' as the user themselves and resource constraints as personal capacity.
PROMPT;

        $response = $this->ai->chat(
            prompt: $prompt,
            type: 'freeform',
            context: ['meeting_id' => $meeting->id]
        );

        // Strip markdown code fences if present
        $clean = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($response));

        return json_decode($clean, true) ?? [];
    }

    /**
     * Persist done items extracted from the meeting.
     */
    public function persistDoneItems(ClientMeeting $meeting, array $items): void
    {
        foreach ($items as $item) {
            MeetingDoneItem::create([
                'user_id' => $meeting->user_id,
                'meeting_id' => $meeting->id,
                'project_id' => $meeting->project_id,
                'title' => $item['title'] ?? 'Untitled item',
                'outcome' => $item['outcome'] ?? null,
                'outcome_metric' => $item['outcome_metric'] ?? null,
                'client_quote' => $item['client_quote'] ?? null,
                'value_delivered' => $item['value_delivered'] ?? null,
                'save_as_testimonial' => $item['save_as_testimonial'] ?? false,
            ]);
        }
    }

    /**
     * Persist scope items (in-scope, out-of-scope, assumptions, risks).
     */
    public function persistScopeItems(ClientMeeting $meeting, array $items): void
    {
        foreach ($items as $item) {
            MeetingScopeItem::create([
                'user_id' => $meeting->user_id,
                'meeting_id' => $meeting->id,
                'description' => $item['description'] ?? 'Untitled scope item',
                'type' => $item['type'] ?? 'assumption',
                'client_quote' => $item['client_quote'] ?? null,
                'confirmed_with_client' => $item['confirmed_with_client'] ?? false,
            ]);
        }
    }

    /**
     * Persist deferred items -- items explicitly set aside during the meeting.
     */
    public function persistDeferredItems(ClientMeeting $meeting, array $items): void
    {
        foreach ($items as $item) {
            DeferredItem::create([
                'user_id' => $meeting->user_id,
                'meeting_id' => $meeting->id,
                'project_id' => $meeting->project_id,
                'title' => $item['title'] ?? 'Untitled deferred item',
                'client_name' => $meeting->isSelfMeeting()
                    ? 'Internal'
                    : $meeting->project?->client_name,
                'client_quote' => $item['client_quote'] ?? null,
                'why_it_matters' => $item['why_it_matters'] ?? null,
                'deferral_reason' => $item['reason'] ?? 'priority',
                'opportunity_type' => $item['opportunity_type'] ?? 'none',
                'estimated_value' => $item['estimated_value'] ?? null,
                'revisit_trigger' => $item['revisit_trigger'] ?? null,
                'status' => 'someday',
                'deferred_on' => $meeting->meeting_date,
                'client_type' => $meeting->client_type ?? 'external',
            ]);
        }
    }

    /**
     * Persist resource signals -- mentions of constraints that explain deferrals.
     */
    public function persistResourceSignals(ClientMeeting $meeting, array $signals): void
    {
        foreach ($signals as $signal) {
            MeetingResourceSignal::create([
                'user_id' => $meeting->user_id,
                'meeting_id' => $meeting->id,
                'resource_type' => $signal['resource_type'] ?? 'budget',
                'description' => $signal['description'] ?? 'Resource constraint mentioned',
                'client_quote' => $signal['client_quote'] ?? null,
                'constraint_timeline' => $signal['constraint_timeline'] ?? null,
            ]);
        }
    }

    /**
     * Persist action items as tasks.
     * Only creates tasks for items assigned to the user (not client-only items).
     */
    public function persistActionItems(ClientMeeting $meeting, array $items): void
    {
        foreach ($items as $item) {
            $assignedTo = $item['assigned_to'] ?? 'user';

            if (in_array($assignedTo, ['user', 'both'])) {
                $lifeAreaId = $meeting->project?->life_area_id
                    ?? LifeArea::where('name', 'Business')->value('id')
                    ?? LifeArea::first()?->id;

                Task::create([
                    'user_id' => $meeting->user_id,
                    'project_id' => $meeting->project_id,
                    'life_area_id' => $lifeAreaId,
                    'title' => $item['title'] ?? 'Action item from meeting',
                    'status' => 'todo',
                    'priority' => $item['priority'] ?? 'medium',
                    'due_date' => isset($item['due']) ? \Carbon\Carbon::parse($item['due']) : null,
                    'notes' => "From meeting: {$meeting->title} on {$meeting->meeting_date->format('M j, Y')}",
                    'is_leaf' => true,
                    'decomposition_status' => 'ready',
                    'two_minute_check' => false,
                ]);
            }
        }
    }

    /**
     * Notify the user about the completed meeting analysis.
     */
    public function notifyUser(ClientMeeting $meeting, ?array $extracted = null): void
    {
        if (!auth()->check()) {
            return;
        }

        $doneCount = count($extracted['done_items'] ?? []);
        $deferredCount = count($extracted['deferred_items'] ?? []);
        $actionCount = count($extracted['action_items'] ?? []);

        Notification::make()
            ->title('Meeting analyzed: ' . $meeting->title)
            ->body(
                "{$actionCount} action items, {$doneCount} done items, {$deferredCount} deferred items"
            )
            ->success()
            ->sendToDatabase(auth()->user());
    }
}
