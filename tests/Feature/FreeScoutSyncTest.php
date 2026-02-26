<?php

namespace Tests\Feature;

use App\Models\EmailContact;
use App\Models\EmailConversation;
use App\Models\User;
use App\Services\EmailIntelligenceService;
use App\Services\FreeScoutApiClient;
use App\Services\FreeScoutSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class FreeScoutSyncTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_sync_mailboxes_creates_records(): void
    {
        $client = Mockery::mock(FreeScoutApiClient::class);
        $client->shouldReceive('listMailboxes')->once()->andReturn([
            '_embedded' => [
                'mailboxes' => [
                    ['id' => 1, 'name' => 'Support', 'email' => 'support@test.com'],
                    ['id' => 2, 'name' => 'Sales', 'email' => 'sales@test.com'],
                ],
            ],
        ]);

        $intelligence = Mockery::mock(EmailIntelligenceService::class);
        $sync = new FreeScoutSyncService($client, $intelligence);

        $result = $sync->syncMailboxes($this->user);

        $this->assertEquals(2, $result->count());
        $this->assertDatabaseHas('freescout_mailboxes', [
            'freescout_mailbox_id' => 1,
            'name' => 'Support',
        ]);
    }

    public function test_resolve_contact_creates_new_contact(): void
    {
        $client = Mockery::mock(FreeScoutApiClient::class);
        $intelligence = Mockery::mock(EmailIntelligenceService::class);
        $sync = new FreeScoutSyncService($client, $intelligence);

        $contact = $sync->resolveContact($this->user, [
            'id' => 42,
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john@example.com',
            'company' => 'ACME Corp',
        ]);

        $this->assertInstanceOf(EmailContact::class, $contact);
        $this->assertEquals('john@example.com', $contact->email);
        $this->assertEquals(42, $contact->freescout_customer_id);
        $this->assertDatabaseHas('email_contacts', ['email' => 'john@example.com']);
    }

    public function test_resolve_contact_finds_existing_by_freescout_id(): void
    {
        $existing = EmailContact::factory()->create([
            'user_id' => $this->user->id,
            'freescout_customer_id' => 42,
            'email' => 'john@example.com',
        ]);

        $client = Mockery::mock(FreeScoutApiClient::class);
        $intelligence = Mockery::mock(EmailIntelligenceService::class);
        $sync = new FreeScoutSyncService($client, $intelligence);

        $contact = $sync->resolveContact($this->user, [
            'id' => 42,
            'firstName' => 'John Updated',
            'email' => 'john@example.com',
        ]);

        $this->assertEquals($existing->id, $contact->id);
        $this->assertEquals('John Updated', $contact->first_name);
    }

    public function test_import_thread_maps_types_correctly(): void
    {
        $conversation = EmailConversation::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $client = Mockery::mock(FreeScoutApiClient::class);
        $intelligence = Mockery::mock(EmailIntelligenceService::class);
        $sync = new FreeScoutSyncService($client, $intelligence);

        // FreeScout uses 'message' for agent replies, we map to 'agent'
        $thread = $sync->importThread($conversation, [
            'id' => 999,
            'type' => 'message',
            'body' => 'Agent reply',
            'createdBy' => ['firstName' => 'Agent', 'email' => 'agent@test.com'],
            'createdAt' => '2026-02-24T10:00:00Z',
        ]);

        $this->assertEquals('agent', $thread->type);
        $this->assertEquals('Agent reply', $thread->body);
        $this->assertDatabaseHas('email_threads', ['freescout_thread_id' => 999]);
    }

    public function test_sync_deduplicates_by_freescout_id(): void
    {
        EmailConversation::factory()->create([
            'user_id' => $this->user->id,
            'freescout_conversation_id' => 100,
            'subject' => 'Original Subject',
        ]);

        $this->assertDatabaseCount('email_conversations', 1);

        // The sync service uses updateOrCreate, so importing again should update, not duplicate
        $client = Mockery::mock(FreeScoutApiClient::class);
        $client->shouldReceive('getConversation')->andReturn([
            'id' => 100,
            'subject' => 'Updated Subject',
            'status' => 'active',
            'type' => 'email',
            '_embedded' => ['threads' => []],
        ]);

        $intelligence = Mockery::mock(EmailIntelligenceService::class);

        $sync = new FreeScoutSyncService($client, $intelligence);
        $sync->importConversation($this->user, ['id' => 100]);

        $this->assertDatabaseCount('email_conversations', 1);
        $this->assertDatabaseHas('email_conversations', [
            'freescout_conversation_id' => 100,
            'subject' => 'Updated Subject',
        ]);
    }
}
