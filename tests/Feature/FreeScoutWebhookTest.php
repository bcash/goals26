<?php

namespace Tests\Feature;

use App\Models\EmailConversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FreeScoutWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        User::factory()->create();
        config(['services.freescout.url' => 'https://test.freescout.io']);
        config(['services.freescout.api_key' => 'test-key']);
        config(['services.freescout.webhook_secret' => null]);
    }

    public function test_webhook_handles_status_change(): void
    {
        $conversation = EmailConversation::factory()->create([
            'freescout_conversation_id' => 100,
            'status' => 'active',
        ]);

        $response = $this->postJson('/webhooks/freescout', [
            'event' => 'convo.status',
            'conversationId' => 100,
            'conversation' => [
                'id' => 100,
                'status' => 'closed',
            ],
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('email_conversations', [
            'freescout_conversation_id' => 100,
            'status' => 'closed',
        ]);
    }

    public function test_webhook_rejects_invalid_secret(): void
    {
        config(['services.freescout.webhook_secret' => 'my-secret']);

        $response = $this->postJson('/webhooks/freescout', [
            'event' => 'convo.created',
        ], [
            'X-FreeScout-Webhook-Secret' => 'wrong-secret',
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_accepts_valid_secret(): void
    {
        config(['services.freescout.webhook_secret' => 'my-secret']);

        Http::fake([
            'test.freescout.io/*' => Http::response([
                'id' => 200,
                'subject' => 'Webhook test',
                'status' => 'active',
                'type' => 'email',
                '_embedded' => ['threads' => []],
            ]),
        ]);

        $response = $this->postJson('/webhooks/freescout', [
            'event' => 'convo.created',
            'conversation' => ['id' => 200, 'subject' => 'Test'],
        ], [
            'X-FreeScout-Webhook-Secret' => 'my-secret',
        ]);

        $response->assertOk();
    }

    public function test_webhook_handles_unknown_events_gracefully(): void
    {
        $response = $this->postJson('/webhooks/freescout', [
            'event' => 'unknown.event',
        ]);

        $response->assertOk();
    }
}
