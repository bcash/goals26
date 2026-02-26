<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FreeScoutApiClient
{
    /**
     * Build the base HTTP client with authentication.
     */
    protected function client(): PendingRequest
    {
        return Http::baseUrl(rtrim(config('services.freescout.url'), '/').'/api')
            ->withHeaders([
                'X-FreeScout-API-Key' => config('services.freescout.api_key'),
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->throw();
    }

    // ── Mailboxes ─────────────────────────────────────────────────────

    /**
     * List all mailboxes.
     *
     * @return array{_embedded: array{mailboxes: array}, page: array}
     */
    public function listMailboxes(): array
    {
        try {
            $response = $this->client()->get('mailboxes');

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::warning('FreeScout: Failed to list mailboxes: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get folders for a mailbox.
     */
    public function getMailboxFolders(int $mailboxId): array
    {
        try {
            $response = $this->client()->get("mailboxes/{$mailboxId}/folders");

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::warning("FreeScout: Failed to get folders for mailbox {$mailboxId}: ".$e->getMessage());

            return [];
        }
    }

    // ── Conversations ─────────────────────────────────────────────────

    /**
     * List conversations with optional filters.
     *
     * @param  array  $filters  Supported: mailboxId, status, type, assignedTo, customerEmail, tag, sortField, sortOrder
     */
    public function listConversations(array $filters = [], int $page = 1): array
    {
        try {
            $params = array_merge($filters, ['page' => $page]);
            $response = $this->client()->get('conversations', $params);

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::warning('FreeScout: Failed to list conversations: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get a single conversation by ID.
     *
     * @param  bool  $embedThreads  Whether to include threads in the response
     */
    public function getConversation(int $id, bool $embedThreads = true): array
    {
        try {
            $params = $embedThreads ? ['embed' => 'threads'] : [];
            $response = $this->client()->get("conversations/{$id}", $params);

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::warning("FreeScout: Failed to get conversation {$id}: ".$e->getMessage());

            return [];
        }
    }

    /**
     * Update a conversation (status, assignee, tags, etc.).
     */
    public function updateConversation(int $id, array $data): void
    {
        try {
            $this->client()->put("conversations/{$id}", $data);
        } catch (\Exception $e) {
            Log::warning("FreeScout: Failed to update conversation {$id}: ".$e->getMessage());
        }
    }

    // ── Threads ───────────────────────────────────────────────────────

    /**
     * Create a note on a conversation.
     */
    public function createNote(int $conversationId, string $body, ?int $userId = null): array
    {
        try {
            $payload = [
                'type' => 'note',
                'body' => $body,
            ];

            if ($userId) {
                $payload['user'] = $userId;
            }

            $response = $this->client()->post("conversations/{$conversationId}/threads", $payload);

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::warning("FreeScout: Failed to create note on conversation {$conversationId}: ".$e->getMessage());

            return [];
        }
    }

    /**
     * Create an agent reply on a conversation.
     */
    public function createReply(int $conversationId, string $body, ?int $userId = null): array
    {
        try {
            $payload = [
                'type' => 'message',
                'body' => $body,
            ];

            if ($userId) {
                $payload['user'] = $userId;
            }

            $response = $this->client()->post("conversations/{$conversationId}/threads", $payload);

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::warning("FreeScout: Failed to create reply on conversation {$conversationId}: ".$e->getMessage());

            return [];
        }
    }

    // ── Customers ─────────────────────────────────────────────────────

    /**
     * List customers with optional filters.
     *
     * @param  array  $filters  Supported: firstName, lastName, email, phone, modifiedSince
     */
    public function listCustomers(array $filters = [], int $page = 1): array
    {
        try {
            $params = array_merge($filters, ['page' => $page]);
            $response = $this->client()->get('customers', $params);

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::warning('FreeScout: Failed to list customers: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get a single customer by ID.
     */
    public function getCustomer(int $id): array
    {
        try {
            $response = $this->client()->get("customers/{$id}");

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::warning("FreeScout: Failed to get customer {$id}: ".$e->getMessage());

            return [];
        }
    }

    // ── Users (Team) ──────────────────────────────────────────────────

    /**
     * List FreeScout users (agents/team members).
     */
    public function listUsers(): array
    {
        try {
            $response = $this->client()->get('users');

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::warning('FreeScout: Failed to list users: '.$e->getMessage());

            return [];
        }
    }

    // ── Tags ──────────────────────────────────────────────────────────

    /**
     * Update tags on a conversation.
     */
    public function updateTags(int $conversationId, array $tags): void
    {
        try {
            $this->client()->put("conversations/{$conversationId}/tags", [
                'tags' => $tags,
            ]);
        } catch (\Exception $e) {
            Log::warning("FreeScout: Failed to update tags on conversation {$conversationId}: ".$e->getMessage());
        }
    }

    // ── Webhooks ──────────────────────────────────────────────────────

    /**
     * Register a webhook for events.
     *
     * @param  array  $events  e.g. ['convo.created', 'convo.customer.reply.created']
     */
    public function createWebhook(string $url, array $events): array
    {
        try {
            $response = $this->client()->post('webhooks', [
                'url' => $url,
                'events' => $events,
            ]);

            return $response->json() ?? [];
        } catch (\Exception $e) {
            Log::warning('FreeScout: Failed to create webhook: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Delete a webhook by ID.
     */
    public function deleteWebhook(int $id): void
    {
        try {
            $this->client()->delete("webhooks/{$id}");
        } catch (\Exception $e) {
            Log::warning("FreeScout: Failed to delete webhook {$id}: ".$e->getMessage());
        }
    }

    // ── Connection Test ───────────────────────────────────────────────

    /**
     * Test the API connection by fetching mailboxes.
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->client()->get('mailboxes');

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
