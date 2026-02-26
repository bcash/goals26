<?php

namespace Tests\Feature;

use App\Filament\Resources\EmailConversationResource\Pages\ListEmailConversations;
use App\Filament\Resources\EmailConversationResource\Pages\ViewEmailConversation;
use App\Models\EmailContact;
use App\Models\EmailConversation;
use App\Models\EmailThread;
use App\Models\FreeScoutMailbox;
use App\Models\User;
use App\Services\FreeScoutApiClient;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmailConversationResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        Filament::setCurrentPanel(
            Filament::getPanel('admin')
        );
    }

    public function test_can_list_conversations(): void
    {
        $conversations = EmailConversation::factory()
            ->count(3)
            ->create(['user_id' => $this->user->id]);

        Livewire::test(ListEmailConversations::class)
            ->set('activeTab', 'all')
            ->assertCanSeeTableRecords($conversations);
    }

    public function test_can_search_by_subject(): void
    {
        $match = EmailConversation::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Important billing question',
        ]);
        $noMatch = EmailConversation::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'General inquiry',
        ]);

        Livewire::test(ListEmailConversations::class)
            ->set('activeTab', 'all')
            ->searchTable('billing')
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$noMatch]);
    }

    public function test_can_filter_by_needs_review(): void
    {
        $needsReview = EmailConversation::factory()->create([
            'user_id' => $this->user->id,
            'needs_review' => true,
        ]);
        $ok = EmailConversation::factory()->create([
            'user_id' => $this->user->id,
            'needs_review' => false,
        ]);

        Livewire::test(ListEmailConversations::class)
            ->set('activeTab', 'all')
            ->filterTable('needs_review', true)
            ->assertCanSeeTableRecords([$needsReview])
            ->assertCanNotSeeTableRecords([$ok]);
    }

    public function test_tabs_filter_unassigned(): void
    {
        $unassigned = EmailConversation::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'assigned_to_name' => null,
            'assigned_to_email' => null,
        ]);
        $assigned = EmailConversation::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'assigned_to_name' => 'John Doe',
            'assigned_to_email' => 'john@example.com',
        ]);

        Livewire::test(ListEmailConversations::class)
            ->set('activeTab', 'unassigned')
            ->assertCanSeeTableRecords([$unassigned])
            ->assertCanNotSeeTableRecords([$assigned]);
    }

    public function test_tabs_filter_mine(): void
    {
        $mine = EmailConversation::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'assigned_to_email' => $this->user->email,
            'assigned_to_name' => $this->user->name,
        ]);
        $other = EmailConversation::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
            'assigned_to_email' => 'someone-else@example.com',
            'assigned_to_name' => 'Someone Else',
        ]);

        Livewire::test(ListEmailConversations::class)
            ->set('activeTab', 'mine')
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_tabs_filter_closed(): void
    {
        $closed = EmailConversation::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'closed',
        ]);
        $active = EmailConversation::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'active',
        ]);

        Livewire::test(ListEmailConversations::class)
            ->set('activeTab', 'closed')
            ->assertCanSeeTableRecords([$closed])
            ->assertCanNotSeeTableRecords([$active]);
    }

    public function test_can_filter_by_mailbox(): void
    {
        FreeScoutMailbox::factory()->create([
            'user_id' => $this->user->id,
            'freescout_mailbox_id' => 42,
        ]);

        $inMailbox = EmailConversation::factory()->create([
            'user_id' => $this->user->id,
            'freescout_mailbox_id' => 42,
        ]);
        $otherMailbox = EmailConversation::factory()->create([
            'user_id' => $this->user->id,
            'freescout_mailbox_id' => 99,
        ]);

        Livewire::test(ListEmailConversations::class)
            ->set('activeTab', 'all')
            ->filterTable('freescout_mailbox_id', 42)
            ->assertCanSeeTableRecords([$inMailbox])
            ->assertCanNotSeeTableRecords([$otherMailbox]);
    }

    public function test_view_page_shows_threads(): void
    {
        $contact = EmailContact::factory()->create([
            'user_id' => $this->user->id,
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
        ]);

        $conversation = EmailConversation::factory()->create([
            'user_id' => $this->user->id,
            'email_contact_id' => $contact->id,
            'subject' => 'Test conversation',
        ]);

        EmailThread::factory()->fromCustomer()->create([
            'email_conversation_id' => $conversation->id,
            'from_name' => 'Jane Smith',
            'from_email' => 'jane@example.com',
            'body' => '<p>Hello, I need help with my order.</p>',
        ]);

        EmailThread::factory()->fromAgent()->create([
            'email_conversation_id' => $conversation->id,
            'from_name' => 'Support Agent',
            'from_email' => 'support@example.com',
            'body' => '<p>Sure, I can help with that.</p>',
        ]);

        Livewire::test(ViewEmailConversation::class, ['record' => $conversation->id])
            ->assertSee('Test conversation')
            ->assertSee('Jane Smith')
            ->assertSee('Support Agent')
            ->assertSee('jane@example.com');
    }

    public function test_can_send_reply(): void
    {
        $conversation = EmailConversation::factory()->create([
            'user_id' => $this->user->id,
            'freescout_conversation_id' => 12345,
            'status' => 'active',
        ]);

        $mockApiClient = $this->mock(FreeScoutApiClient::class);
        $mockApiClient->shouldReceive('createReply')
            ->once()
            ->with(12345, 'Test reply body')
            ->andReturn(['id' => 1, 'type' => 'message']);

        $mockApiClient->shouldReceive('getConversation')
            ->once()
            ->andReturn([]);

        Livewire::test(ViewEmailConversation::class, ['record' => $conversation->id])
            ->set('replyBody', 'Test reply body')
            ->call('sendReply')
            ->assertHasNoErrors();
    }

    public function test_can_add_note(): void
    {
        $conversation = EmailConversation::factory()->create([
            'user_id' => $this->user->id,
            'freescout_conversation_id' => 12345,
        ]);

        $mockApiClient = $this->mock(FreeScoutApiClient::class);
        $mockApiClient->shouldReceive('createNote')
            ->once()
            ->with(12345, 'Internal test note')
            ->andReturn(['id' => 1, 'type' => 'note']);

        $mockApiClient->shouldReceive('getConversation')
            ->once()
            ->andReturn([]);

        Livewire::test(ViewEmailConversation::class, ['record' => $conversation->id])
            ->set('noteBody', 'Internal test note')
            ->set('showNoteForm', true)
            ->call('addNote')
            ->assertHasNoErrors()
            ->assertSet('showNoteForm', false)
            ->assertSet('noteBody', '');
    }

    public function test_reply_validates_empty_body(): void
    {
        $conversation = EmailConversation::factory()->create([
            'user_id' => $this->user->id,
            'freescout_conversation_id' => 12345,
        ]);

        Livewire::test(ViewEmailConversation::class, ['record' => $conversation->id])
            ->set('replyBody', '')
            ->call('sendReply')
            ->assertNotified('Reply body cannot be empty');
    }
}
