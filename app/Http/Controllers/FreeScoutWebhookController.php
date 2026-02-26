<?php

namespace App\Http\Controllers;

use App\Models\EmailConversation;
use App\Models\User;
use App\Services\FreeScoutSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class FreeScoutWebhookController extends Controller
{
    public function __construct(
        protected FreeScoutSyncService $sync
    ) {}

    /**
     * Handle incoming FreeScout webhook events.
     */
    public function handle(Request $request): JsonResponse
    {
        // Verify webhook secret if configured
        $secret = config('services.freescout.webhook_secret');
        if ($secret && $request->header('X-FreeScout-Webhook-Secret') !== $secret) {
            Log::warning('FreeScout webhook: invalid secret');

            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $event = $request->input('event') ?? $request->input('type') ?? 'unknown';
        $data = $request->all();

        Log::info("FreeScout webhook received: {$event}");

        $user = User::first();

        if (! $user) {
            return response()->json(['error' => 'No user configured'], 500);
        }

        try {
            match ($event) {
                'convo.created' => $this->handleConversationCreated($user, $data),
                'convo.customer.reply.created' => $this->handleNewReply($user, $data, 'customer'),
                'convo.agent.reply.created' => $this->handleNewReply($user, $data, 'agent'),
                'convo.status' => $this->handleStatusChange($data),
                default => Log::info("FreeScout webhook: unhandled event {$event}"),
            };
        } catch (\Exception $e) {
            Log::error("FreeScout webhook error ({$event}): ".$e->getMessage());

            return response()->json(['error' => 'Processing failed'], 500);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Handle new conversation creation.
     */
    private function handleConversationCreated(User $user, array $data): void
    {
        $conversationData = $data['conversation'] ?? $data;
        $this->sync->importConversation($user, $conversationData);
    }

    /**
     * Handle a new reply (customer or agent).
     */
    private function handleNewReply(User $user, array $data, string $type): void
    {
        $conversationId = $data['conversation']['id'] ?? $data['conversationId'] ?? null;

        if (! $conversationId) {
            return;
        }

        // Re-sync the full conversation to get the new thread
        $conversationData = $data['conversation'] ?? ['id' => $conversationId];
        $this->sync->importConversation($user, $conversationData);
    }

    /**
     * Handle conversation status change.
     */
    private function handleStatusChange(array $data): void
    {
        $conversationId = $data['conversation']['id'] ?? $data['conversationId'] ?? null;
        $newStatus = $data['conversation']['status'] ?? $data['status'] ?? null;

        if (! $conversationId || ! $newStatus) {
            return;
        }

        EmailConversation::where('freescout_conversation_id', $conversationId)
            ->update(['status' => $newStatus]);
    }
}
