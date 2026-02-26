<?php

namespace Tests\Feature;

use App\Filament\Resources\EmailContactResource\Pages\CreateEmailContact;
use App\Filament\Resources\EmailContactResource\Pages\ListEmailContacts;
use App\Models\EmailContact;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmailContactResourceTest extends TestCase
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

    public function test_can_list_contacts(): void
    {
        $contacts = EmailContact::factory()
            ->count(3)
            ->create(['user_id' => $this->user->id]);

        Livewire::test(ListEmailContacts::class)
            ->assertCanSeeTableRecords($contacts);
    }

    public function test_can_search_by_email(): void
    {
        $match = EmailContact::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'john@example.com',
        ]);
        $noMatch = EmailContact::factory()->create([
            'user_id' => $this->user->id,
            'email' => 'jane@other.com',
        ]);

        Livewire::test(ListEmailContacts::class)
            ->searchTable('john@example')
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$noMatch]);
    }

    public function test_can_filter_by_contact_type(): void
    {
        $client = EmailContact::factory()->create([
            'user_id' => $this->user->id,
            'contact_type' => 'client',
        ]);
        $vendor = EmailContact::factory()->create([
            'user_id' => $this->user->id,
            'contact_type' => 'vendor',
        ]);

        Livewire::test(ListEmailContacts::class)
            ->filterTable('contact_type', 'client')
            ->assertCanSeeTableRecords([$client])
            ->assertCanNotSeeTableRecords([$vendor]);
    }

    public function test_can_create_contact(): void
    {
        Livewire::test(CreateEmailContact::class)
            ->fillForm([
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'contact_type' => 'client',
                'company' => 'ACME Corp',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('email_contacts', [
            'email' => 'john@example.com',
            'contact_type' => 'client',
        ]);
    }
}
