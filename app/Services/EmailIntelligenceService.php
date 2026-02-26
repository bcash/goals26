<?php

namespace App\Services;

use App\Models\DeferredItem;
use App\Models\EmailConversation;
use App\Models\EmailThread;
use Illuminate\Support\Facades\Log;

class EmailIntelligenceService
{
    public function __construct(
        protected AiService $ai,
        protected DeferralService $deferral
    ) {}

    /**
     * Full analysis pipeline for an email conversation.
     * Extracts summary, sentiment, action items, and scores agent responses.
     */
    public function analyzeConversation(EmailConversation $conversation): void
    {
        $conversation->update(['analysis_status' => 'processing']);

        try {
            $conversation->load(['threads', 'contact']);

            $extracted = $this->extractAll($conversation);

            // Persist AI analysis on conversation
            $conversation->update([
                'ai_summary' => $extracted['summary'] ?? null,
                'ai_sentiment' => $extracted['sentiment'] ?? null,
                'ai_priority_score' => $extracted['priority_score'] ?? null,
                'needs_review' => ($extracted['priority_score'] ?? 0) >= 8
                    || ($extracted['sentiment'] ?? '') === 'escalation',
                'analysis_status' => 'complete',
            ]);

            // Create deferred items for action items
            $this->persistActionItems($conversation, $extracted['action_items'] ?? []);

            // Score agent responses
            $agentThreads = $conversation->threads()
                ->fromAgent()
                ->whereNull('ai_quality_score')
                ->get();

            foreach ($agentThreads as $thread) {
                try {
                    $this->scoreAgentResponse($thread);
                } catch (\Exception $e) {
                    Log::warning("Failed to score agent thread {$thread->id}: ".$e->getMessage());
                }
            }

        } catch (\Exception $e) {
            $conversation->update(['analysis_status' => 'failed']);
            Log::error("Email analysis failed for conversation {$conversation->id}: ".$e->getMessage());

            throw $e;
        }
    }

    /**
     * Extract intelligence from the full conversation thread history.
     * Returns structured JSON with summary, sentiment, action items, etc.
     */
    public function extractAll(EmailConversation $conversation): array
    {
        $contactName = $conversation->contact
            ? $conversation->contact->fullName()
            : 'Unknown';
        $contactCompany = $conversation->contact?->company ?? 'Unknown';
        $contactType = $conversation->contact?->contact_type ?? 'other';

        // Build the thread history for the prompt
        $threadHistory = $conversation->threads
            ->map(function (EmailThread $thread) {
                $label = match ($thread->type) {
                    'customer' => "CUSTOMER ({$thread->from_name})",
                    'agent' => "AGENT ({$thread->from_name})",
                    'note' => "INTERNAL NOTE ({$thread->from_name})",
                    default => $thread->type,
                };
                $date = $thread->message_at?->format('M j, Y g:ia') ?? 'Unknown date';

                // Strip HTML tags from body for cleaner analysis
                $body = strip_tags($thread->body);

                return "[{$date}] {$label}:\n{$body}";
            })
            ->join("\n\n---\n\n");

        $prompt = <<<PROMPT
You are a business communication analyst. Analyze this email conversation and extract structured intelligence.

SUBJECT: "{$conversation->subject}"
CONTACT: {$contactName} ({$contactCompany}) — type: {$contactType}
MAILBOX CATEGORY: {$conversation->category}
STATUS: {$conversation->status}

THREAD HISTORY:
{$threadHistory}

Extract the following in a single JSON response:

{
  "summary": "2-3 sentence summary of the conversation and its current state",

  "sentiment": "positive | neutral | negative | escalation",

  "priority_score": 1-10 (10 = extremely urgent/important),

  "action_items": [
    {
      "title": "Clear action needed",
      "assigned_to": "user | team | client",
      "urgency": "high | medium | low",
      "category": "task | delegation | research | followup | billing",
      "due_hint": "any date or timeline mentioned, or null",
      "context": "Brief context about why this matters"
    }
  ],

  "follow_ups": [
    {
      "title": "What needs follow-up",
      "trigger": "When or why to follow up",
      "days_out": estimated days from now (integer or null)
    }
  ],

  "commercial_signals": {
    "opportunity_detected": true or false,
    "opportunity_type": "upsell | new-project | retainer | referral | none",
    "estimated_value": null or number,
    "signal_quote": "Relevant quote from the conversation"
  },

  "customer_satisfaction": {
    "score": 1-10,
    "indicators": ["positive or negative indicators observed"],
    "at_risk": true or false
  }
}

Be conservative with estimates. Focus on actionable intelligence.
For vendor/supplier conversations, focus on cost, timeline, and delivery risks.
PROMPT;

        $response = $this->ai->chat(
            prompt: $prompt,
            type: 'freeform',
            context: ['email_conversation_id' => $conversation->id]
        );

        $clean = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($response));

        return json_decode($clean, true) ?? [];
    }

    /**
     * Score an individual agent response for quality.
     */
    public function scoreAgentResponse(EmailThread $thread): void
    {
        if ($thread->type !== 'agent') {
            return;
        }

        $conversation = $thread->conversation;
        $conversation->load('contact');

        // Get the customer message this agent is responding to
        $previousCustomerThread = $conversation->threads()
            ->fromCustomer()
            ->where('message_at', '<', $thread->message_at)
            ->latest('message_at')
            ->first();

        $customerMessage = $previousCustomerThread
            ? strip_tags($previousCustomerThread->body)
            : 'No customer message found';

        $agentResponse = strip_tags($thread->body);
        $contactName = $conversation->contact?->fullName() ?? 'Customer';

        $prompt = <<<PROMPT
You are a customer service quality analyst. Score this agent response.

CUSTOMER ({$contactName}) asked:
{$customerMessage}

AGENT ({$thread->from_name}) replied:
{$agentResponse}

Score the response on a scale of 1-10 and provide brief feedback. Return JSON:

{
  "score": 1-10,
  "notes": "Brief quality assessment focusing on: tone, completeness, accuracy, professionalism, and helpfulness. Highlight specific strengths and areas for improvement."
}
PROMPT;

        $response = $this->ai->chat(
            prompt: $prompt,
            type: 'freeform',
            context: ['email_thread_id' => $thread->id]
        );

        $clean = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($response));
        $result = json_decode($clean, true) ?? [];

        $thread->update([
            'ai_quality_score' => $result['score'] ?? null,
            'ai_quality_notes' => $result['notes'] ?? null,
        ]);

        // Flag conversation for review if score is low
        if (($result['score'] ?? 10) <= 5) {
            $conversation->update(['needs_review' => true]);
        }
    }

    /**
     * Persist extracted action items as DeferredItems.
     */
    public function persistActionItems(EmailConversation $conversation, array $items): void
    {
        foreach ($items as $item) {
            $assignedTo = $item['assigned_to'] ?? 'user';

            // Only create deferred items for things the user or team needs to act on
            if (in_array($assignedTo, ['user', 'team'])) {
                DeferredItem::create([
                    'user_id' => $conversation->user_id,
                    'email_conversation_id' => $conversation->id,
                    'project_id' => $conversation->project_id,
                    'title' => $item['title'] ?? 'Action from email',
                    'description' => $item['context'] ?? null,
                    'client_name' => $conversation->contact?->fullName(),
                    'client_context' => "Email: {$conversation->subject}",
                    'deferral_reason' => $this->mapCategoryToDeferralReason($item['category'] ?? 'task'),
                    'opportunity_type' => 'none',
                    'status' => ($item['urgency'] ?? 'medium') === 'high' ? 'scheduled' : 'someday',
                    'deferred_on' => today(),
                    'revisit_date' => isset($item['due_hint'])
                        ? $this->parseDueHint($item['due_hint'])
                        : null,
                    'why_it_matters' => $item['context'] ?? null,
                ]);
            }
        }
    }

    /**
     * Map email action category to deferral reason.
     */
    private function mapCategoryToDeferralReason(string $category): string
    {
        return match ($category) {
            'delegation' => 'scope-control',
            'research' => 'awaiting-decision',
            'followup' => 'timeline',
            'billing' => 'budget',
            default => 'priority',
        };
    }

    /**
     * Try to parse a due hint string into a Carbon date.
     */
    private function parseDueHint(?string $hint): ?\Carbon\Carbon
    {
        if (! $hint) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($hint);
        } catch (\Exception) {
            return null;
        }
    }
}
