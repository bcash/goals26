<?php

namespace Tests\Feature;

use App\Services\FreeScoutApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FreeScoutApiClientTest extends TestCase
{
    use RefreshDatabase;

    private FreeScoutApiClient $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new FreeScoutApiClient;
        config(['services.freescout.url' => 'https://test.freescout.io']);
        config(['services.freescout.api_key' => 'test-key']);
    }

    public function test_list_mailboxes_returns_data(): void
    {
        Http::fake([
            'test.freescout.io/api/mailboxes*' => Http::response([
                '_embedded' => [
                    'mailboxes' => [
                        ['id' => 1, 'name' => 'Support', 'email' => 'support@test.com'],
                    ],
                ],
                'page' => ['totalElements' => 1, 'totalPages' => 1],
            ]),
        ]);

        $result = $this->client->listMailboxes();

        $this->assertArrayHasKey('_embedded', $result);
        $this->assertCount(1, $result['_embedded']['mailboxes']);
    }

    public function test_list_conversations_with_filters(): void
    {
        Http::fake([
            'test.freescout.io/api/conversations*' => Http::response([
                '_embedded' => [
                    'conversations' => [
                        ['id' => 100, 'subject' => 'Test conversation'],
                    ],
                ],
                'page' => ['totalElements' => 1, 'totalPages' => 1],
            ]),
        ]);

        $result = $this->client->listConversations(['status' => 'active']);

        $this->assertArrayHasKey('_embedded', $result);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'status=active');
        });
    }

    public function test_get_conversation_with_threads(): void
    {
        Http::fake([
            'test.freescout.io/api/conversations/100*' => Http::response([
                'id' => 100,
                'subject' => 'Test',
                '_embedded' => [
                    'threads' => [
                        ['id' => 1, 'type' => 'customer', 'body' => 'Hello'],
                    ],
                ],
            ]),
        ]);

        $result = $this->client->getConversation(100);

        $this->assertEquals(100, $result['id']);
        $this->assertCount(1, $result['_embedded']['threads']);
    }

    public function test_create_note_sends_correct_payload(): void
    {
        Http::fake([
            'test.freescout.io/api/conversations/100/threads' => Http::response(['id' => 50], 201),
        ]);

        $result = $this->client->createNote(100, 'Test note body');

        $this->assertEquals(50, $result['id']);
        Http::assertSent(function ($request) {
            $body = $request->data();

            return $body['type'] === 'note' && $body['body'] === 'Test note body';
        });
    }

    public function test_returns_empty_on_api_failure(): void
    {
        Http::fake([
            'test.freescout.io/api/mailboxes*' => Http::response('Server Error', 500),
        ]);

        $result = $this->client->listMailboxes();

        $this->assertEquals([], $result);
    }

    public function test_connection_test_returns_true_on_success(): void
    {
        Http::fake([
            'test.freescout.io/api/mailboxes*' => Http::response(['_embedded' => ['mailboxes' => []]]),
        ]);

        $this->assertTrue($this->client->testConnection());
    }

    public function test_connection_test_returns_false_on_failure(): void
    {
        Http::fake([
            'test.freescout.io/api/mailboxes*' => Http::response('Unauthorized', 401),
        ]);

        $this->assertFalse($this->client->testConnection());
    }
}
