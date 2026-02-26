<?php

namespace App\Services;

use App\Models\EmailContact;
use App\Models\EmailConversation;
use App\Models\EmailThread;
use App\Models\FreeScoutMailbox;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class FreeScoutSyncService
{
    public function __construct(
        protected FreeScoutApiClient $client,
        protected EmailIntelligenceService $intelligence
    ) {}

    /**
     * Sync mailboxes from FreeScout.
     * Fetches all available mailboxes and creates/updates local records.
     */
    public function syncMailboxes(User $user): Collection
    {
        $synced = collect();

        try {
            $response = $this->client->listMailboxes();
            $mailboxes = $response['_embedded']['mailboxes'] ?? [];

            foreach ($mailboxes as $mailbox) {
                $record = FreeScoutMailbox::updateOrCreate(
                    ['freescout_mailbox_id' => $mailbox['id']],
                    [
                        'user_id' => $user->id,
                        'name' => $mailbox['name'] ?? 'Unknown',
                        'email' => $mailbox['email'] ?? '',
                    ]
                );
                $synced->push($record);
            }
        } catch (\Exception $e) {
            Log::error('FreeScout mailbox sync failed: '.$e->getMessage());
        }

        return $synced;
    }

    /**
     * Sync conversations from all enabled mailboxes.
     * Fetches conversations modified in the last N days.
     * Returns count of synced conversations (not the models, to conserve memory).
     */
    public function syncConversations(User $user, int $days = 7, bool $analyze = false, ?\Closure $onProgress = null): int
    {
        $syncedCount = 0;
        $mailboxes = FreeScoutMailbox::where('user_id', $user->id)
            ->enabled()
            ->get();

        foreach ($mailboxes as $mailbox) {
            try {
                $page = 1;
                $hasMore = true;

                while ($hasMore) {
                    $response = $this->client->listConversations([
                        'mailboxId' => $mailbox->freescout_mailbox_id,
                        'modifiedSince' => now()->subDays($days)->toIso8601String(),
                        'sortField' => 'modifiedAt',
                        'sortOrder' => 'desc',
                    ], $page);

                    $conversations = $response['_embedded']['conversations'] ?? [];

                    foreach ($conversations as $convoData) {
                        try {
                            $this->importConversation($user, $convoData, $mailbox, $analyze);
                            $syncedCount++;

                            if ($onProgress) {
                                $onProgress($syncedCount, $convoData['subject'] ?? '');
                            }
                        } catch (\Exception $e) {
                            $convoId = $convoData['id'] ?? 'unknown';
                            Log::warning("FreeScout: Failed to import conversation {$convoId}: ".$e->getMessage());
                        }
                    }

                    $totalPages = $response['page']['totalPages'] ?? 1;
                    $hasMore = $page < $totalPages;
                    $page++;

                    // Free memory between pages
                    unset($conversations, $response);
                    gc_collect_cycles();
                }

                $mailbox->update(['last_synced_at' => now()]);
            } catch (\Exception $e) {
                Log::error("FreeScout: Failed to sync mailbox {$mailbox->name}: ".$e->getMessage());
            }
        }

        return $syncedCount;
    }

    /**
     * Sync customer records from FreeScout.
     * Returns count of synced contacts (not the models, to conserve memory).
     */
    public function syncContacts(User $user): int
    {
        $syncedCount = 0;

        try {
            $page = 1;
            $hasMore = true;

            while ($hasMore) {
                $response = $this->client->listCustomers([], $page);
                $customers = $response['_embedded']['customers'] ?? [];

                foreach ($customers as $customerData) {
                    try {
                        $this->resolveContact($user, $customerData);
                        $syncedCount++;
                    } catch (\Exception $e) {
                        Log::warning('FreeScout: Failed to import customer: '.$e->getMessage());
                    }
                }

                $totalPages = $response['page']['totalPages'] ?? 1;
                $hasMore = $page < $totalPages;
                $page++;

                // Free memory between pages
                unset($customers, $response);
                gc_collect_cycles();
            }
        } catch (\Exception $e) {
            Log::error('FreeScout contact sync failed: '.$e->getMessage());
        }

        return $syncedCount;
    }

    /**
     * Import a single conversation with its threads.
     * Set $analyze to true to run AI analysis immediately (costly — prefer freescout:analyze command).
     */
    public function importConversation(User $user, array $data, ?FreeScoutMailbox $mailbox = null, bool $analyze = false): EmailConversation
    {
        $convoId = $data['id'] ?? $data['number'] ?? 0;

        // Resolve the customer/contact
        $contact = null;
        if (isset($data['customer'])) {
            $contact = $this->resolveContact($user, $data['customer']);
        }

        // Get full conversation with threads — the list endpoint returns empty threads,
        // so we must always fetch the individual conversation to get actual thread data.
        $threads = $data['_embedded']['threads'] ?? [];
        if (empty($threads)) {
            $fullConvo = $this->client->getConversation($convoId, true);
            $threads = $fullConvo['_embedded']['threads'] ?? [];
            $data = array_merge($data, $fullConvo);
        }

        // Determine dates from threads
        $threadDates = collect($threads)->pluck('createdAt')->filter();
        $firstMessageAt = $threadDates->isNotEmpty()
            ? Carbon::parse($threadDates->min())
            : (isset($data['createdAt']) ? Carbon::parse($data['createdAt']) : now());
        $lastMessageAt = $threadDates->isNotEmpty()
            ? Carbon::parse($threadDates->max())
            : $firstMessageAt;

        $conversation = EmailConversation::updateOrCreate(
            ['freescout_conversation_id' => $convoId],
            [
                'user_id' => $user->id,
                'freescout_mailbox_id' => $data['mailboxId'] ?? $mailbox?->freescout_mailbox_id,
                'email_contact_id' => $contact?->id,
                'subject' => $data['subject'] ?? 'No Subject',
                'preview' => \Illuminate\Support\Str::limit($data['preview'] ?? '', 500),
                'status' => $data['status'] ?? 'active',
                'type' => $data['type'] ?? 'email',
                'assigned_to_name' => $data['assignee']['firstName'] ?? null,
                'assigned_to_email' => $data['assignee']['email'] ?? null,
                'tags' => isset($data['tags']) ? collect($data['tags'])->pluck('tag')->all() : null,
                'thread_count' => count($threads),
                'first_message_at' => $firstMessageAt,
                'last_message_at' => $lastMessageAt,
                'last_synced_at' => now(),
            ]
        );

        // Import threads
        foreach ($threads as $threadData) {
            $this->importThread($conversation, $threadData);
        }

        // Update contact stats
        if ($contact) {
            $contact->update([
                'last_contact_at' => $lastMessageAt,
                'conversation_count' => $contact->conversations()->count(),
            ]);
        }

        // Only run AI analysis if explicitly requested (costly — prefer freescout:analyze)
        if ($analyze && ($conversation->wasRecentlyCreated || $conversation->analysis_status === 'pending')) {
            try {
                $this->intelligence->analyzeConversation($conversation);
            } catch (\Exception $e) {
                Log::warning("FreeScout: AI analysis failed for conversation {$convoId}: ".$e->getMessage());
            }
        }

        return $conversation;
    }

    /**
     * Import a single thread into a conversation.
     */
    public function importThread(EmailConversation $conversation, array $data): EmailThread
    {
        $threadId = $data['id'] ?? 0;

        // Map FreeScout thread types to our types
        $type = match ($data['type'] ?? 'customer') {
            'customer' => 'customer',
            'message' => 'agent',
            'note' => 'note',
            default => 'customer',
        };

        return EmailThread::updateOrCreate(
            ['freescout_thread_id' => $threadId],
            [
                'email_conversation_id' => $conversation->id,
                'type' => $type,
                'body' => $data['body'] ?? '',
                'from_name' => $data['createdBy']['firstName'] ?? ($data['customer']['firstName'] ?? null),
                'from_email' => $data['createdBy']['email'] ?? ($data['customer']['email'] ?? null),
                'to_emails' => $data['to'] ?? null,
                'cc_emails' => $data['cc'] ?? null,
                'has_attachments' => ! empty($data['_embedded']['attachments'] ?? []),
                'attachment_count' => count($data['_embedded']['attachments'] ?? []),
                'message_at' => isset($data['createdAt'])
                    ? Carbon::parse($data['createdAt'])
                    : now(),
            ]
        );
    }

    /**
     * Resolve or create an EmailContact from FreeScout customer data.
     */
    public function resolveContact(User $user, array $customerData): EmailContact
    {
        $customerId = $customerData['id'] ?? null;
        $email = $this->extractEmail($customerData);
        $phone = $this->extractPhone($customerData);

        // Try to find by FreeScout customer ID first
        if ($customerId) {
            $existing = EmailContact::where('freescout_customer_id', $customerId)->first();
            if ($existing) {
                return $this->updateContactFromApi($existing, $customerData);
            }
        }

        // Try to find by email
        if ($email) {
            $existing = EmailContact::where('user_id', $user->id)
                ->where('email', $email)
                ->first();
            if ($existing) {
                $existing->update(['freescout_customer_id' => $customerId]);

                return $this->updateContactFromApi($existing, $customerData);
            }
        }

        // Create new contact
        return EmailContact::create([
            'user_id' => $user->id,
            'freescout_customer_id' => $customerId,
            'first_name' => $customerData['firstName'] ?? null,
            'last_name' => $customerData['lastName'] ?? null,
            'email' => $email ?? 'unknown@example.com',
            'phone' => $phone,
            'company' => $customerData['company'] ?? null,
            'job_title' => $customerData['jobTitle'] ?? ($customerData['job_title'] ?? null),
            'contact_type' => 'other',
            'first_contact_at' => now(),
            'last_contact_at' => now(),
        ]);
    }

    /**
     * Push a local ConversationNote to FreeScout as a thread note.
     */
    public function pushNote(\App\Models\ConversationNote $note): void
    {
        $conversation = $note->conversation;

        if (! $conversation?->freescout_conversation_id) {
            Log::warning('Cannot push note: conversation has no FreeScout ID');

            return;
        }

        $result = $this->client->createNote(
            $conversation->freescout_conversation_id,
            $note->content
        );

        if (! empty($result)) {
            $threadId = $result['id'] ?? null;
            $note->update([
                'freescout_thread_id' => $threadId,
                'synced_at' => now(),
            ]);
        }
    }

    /**
     * Update a contact's details from FreeScout API data.
     */
    private function updateContactFromApi(EmailContact $contact, array $data): EmailContact
    {
        $email = $this->extractEmail($data);
        $phone = $this->extractPhone($data);

        $updates = array_filter([
            'first_name' => $data['firstName'] ?? null,
            'last_name' => $data['lastName'] ?? null,
            'company' => $data['company'] ?? null,
            'job_title' => $data['jobTitle'] ?? ($data['job_title'] ?? null),
            'phone' => $phone,
        ]);

        // Update email if we have a real one and the contact currently has the placeholder
        if ($email && $contact->email === 'unknown@example.com') {
            $updates['email'] = $email;
        }

        if (! empty($updates)) {
            $contact->update($updates);
        }

        return $contact->refresh();
    }

    /**
     * Extract email from FreeScout customer data.
     * The API uses different structures depending on the endpoint:
     *   - Conversation embed: top-level 'email' field
     *   - Customer list/detail: nested at '_embedded.emails[].value'
     */
    private function extractEmail(array $data): ?string
    {
        return $data['email']
            ?? ($data['emails'][0]['value'] ?? null)
            ?? ($data['_embedded']['emails'][0]['value'] ?? null);
    }

    /**
     * Extract phone from FreeScout customer data.
     * Same nesting pattern as emails.
     */
    private function extractPhone(array $data): ?string
    {
        return $data['phone']
            ?? ($data['phones'][0]['value'] ?? null)
            ?? ($data['_embedded']['phones'][0]['value'] ?? null);
    }
}
