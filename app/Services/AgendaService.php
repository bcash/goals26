<?php

namespace App\Services;

use App\Models\AgendaItem;
use App\Models\ClientMeeting;
use App\Models\MeetingAgenda;
use App\Models\Project;

class AgendaService
{
    public function __construct(protected AiService $ai) {}

    /**
     * Build a new agenda for an upcoming meeting.
     * Pulls context from previous meetings, open tasks, and deferred items.
     */
    public function buildAgenda(
        Project $project,
        ?ClientMeeting $meeting = null,
        ?string $title = null,
        ?string $clientType = 'external',
        ?string $purpose = null,
        ?string $scheduledFor = null
    ): MeetingAgenda {
        $agenda = MeetingAgenda::create([
            'user_id' => auth()->id(),
            'project_id' => $project->id,
            'client_meeting_id' => $meeting?->id,
            'title' => $title ?? 'Meeting Agenda: ' . $project->name,
            'client_type' => $clientType,
            'client_name' => $project->client_name,
            'scheduled_for' => $scheduledFor,
            'purpose' => $purpose,
            'status' => 'draft',
        ]);

        // Auto-populate standing items
        $this->addStandingItems($agenda);

        // Add open action items from previous meeting
        $this->addOpenActionItems($agenda);

        // Add deferred items due for review
        $this->addDeferredReviewItems($agenda);

        // Generate AI-suggested topics
        $suggestedTopics = $this->suggestTopics($agenda);
        $agenda->update(['ai_suggested_topics' => $suggestedTopics]);

        return $agenda;
    }

    /**
     * Add standard standing items that appear on every agenda.
     */
    public function addStandingItems(MeetingAgenda $agenda): void
    {
        $standingItems = [
            [
                'title' => 'Purpose & desired outcomes',
                'item_type' => 'topic',
                'time_allocation_minutes' => 5,
                'sort_order' => 0,
            ],
        ];

        if ($agenda->project_id) {
            $standingItems[] = [
                'title' => 'Budget & timeline check',
                'item_type' => 'budget-check',
                'time_allocation_minutes' => 5,
                'sort_order' => 1,
            ];
        }

        foreach ($standingItems as $item) {
            AgendaItem::create(array_merge($item, [
                'agenda_id' => $agenda->id,
            ]));
        }
    }

    /**
     * Add open action items from the project as follow-up agenda items.
     */
    public function addOpenActionItems(MeetingAgenda $agenda): void
    {
        if (!$agenda->project_id) {
            return;
        }

        $openTasks = $agenda->openActionItems()->take(5);
        $sort = 10;

        foreach ($openTasks as $task) {
            AgendaItem::create([
                'agenda_id' => $agenda->id,
                'title' => 'Follow-up: ' . $task->title,
                'item_type' => 'action-followup',
                'source_type' => 'task',
                'source_id' => $task->id,
                'sort_order' => $sort++,
            ]);
        }
    }

    /**
     * Add deferred items that are due for review as agenda topics.
     */
    public function addDeferredReviewItems(MeetingAgenda $agenda): void
    {
        if (!$agenda->project_id) {
            return;
        }

        $deferredItems = $agenda->deferredItemsForReview()->take(3);
        $sort = 50;

        foreach ($deferredItems as $item) {
            AgendaItem::create([
                'agenda_id' => $agenda->id,
                'title' => 'Revisit: ' . $item->title,
                'description' => $item->revisit_trigger
                    ? "Trigger: {$item->revisit_trigger}"
                    : null,
                'item_type' => 'deferred-review',
                'source_type' => 'deferred_item',
                'source_id' => $item->id,
                'sort_order' => $sort++,
            ]);
        }
    }

    /**
     * Use AI to suggest additional agenda topics based on project history
     * and the stated purpose of the meeting.
     * Returns the suggested topics array.
     */
    public function suggestTopics(MeetingAgenda $agenda): array
    {
        $previousMeetings = $agenda->previousMeetings()
            ->take(3)
            ->map(fn ($m) => "- {$m->meeting_date->format('M j')}: {$m->title} ({$m->meeting_type})")
            ->join("\n");

        $clientContext = $agenda->client_type === 'self'
            ? 'This is an internal planning session. Treat as a self-coaching meeting.'
            : "External client meeting for project: {$agenda->project?->name}";

        $prompt = <<<PROMPT
You are helping prepare a meeting agenda.

MEETING: "{$agenda->title}"
PURPOSE: {$agenda->purpose}
CLIENT CONTEXT: {$clientContext}

PREVIOUS MEETINGS:
{$previousMeetings}

Suggest 3-5 specific agenda topics that would make this meeting productive.
For each topic, suggest a time allocation.

Respond as JSON:
[
  {
    "title": "Topic title",
    "description": "Why this topic matters for this meeting",
    "time_allocation_minutes": 10
  }
]
PROMPT;

        $response = $this->ai->chat($prompt, 'freeform');
        $clean = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($response));
        $topics = json_decode($clean, true);

        return is_array($topics) ? $topics : [];
    }

    /**
     * Mark an agenda as complete and link it to the resulting meeting record.
     */
    public function linkToMeeting(MeetingAgenda $agenda, ClientMeeting $meeting): void
    {
        $agenda->update([
            'client_meeting_id' => $meeting->id,
            'status' => 'complete',
        ]);
    }
}
