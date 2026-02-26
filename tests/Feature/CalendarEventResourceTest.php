<?php

namespace Tests\Feature;

use App\Filament\Resources\CalendarEvents\Pages\CreateCalendarEvent;
use App\Filament\Resources\CalendarEvents\Pages\EditCalendarEvent;
use App\Filament\Resources\CalendarEvents\Pages\ListCalendarEvents;
use App\Models\CalendarEvent;
use App\Models\LifeArea;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CalendarEventResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private LifeArea $lifeArea;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        Filament::setCurrentPanel(
            Filament::getPanel('admin')
        );

        $this->lifeArea = LifeArea::create([
            'user_id' => $this->user->id,
            'name' => 'Business',
        ]);
    }

    public function test_list_page_renders(): void
    {
        CalendarEvent::factory()->count(3)->create(['user_id' => $this->user->id]);

        Livewire::test(ListCalendarEvents::class)
            ->assertSuccessful();
    }

    public function test_list_page_shows_events(): void
    {
        $events = CalendarEvent::factory()->count(3)->create(['user_id' => $this->user->id]);

        Livewire::test(ListCalendarEvents::class)
            ->assertCanSeeTableRecords($events);
    }

    public function test_list_page_search_by_title(): void
    {
        $targetEvent = CalendarEvent::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Sprint Planning Session',
        ]);

        $otherEvent = CalendarEvent::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Coffee Break',
        ]);

        Livewire::test(ListCalendarEvents::class)
            ->searchTable('Sprint Planning')
            ->assertCanSeeTableRecords([$targetEvent])
            ->assertCanNotSeeTableRecords([$otherEvent]);
    }

    public function test_create_page_renders(): void
    {
        Livewire::test(CreateCalendarEvent::class)
            ->assertSuccessful();
    }

    public function test_can_create_calendar_event(): void
    {
        Livewire::test(CreateCalendarEvent::class)
            ->fillForm([
                'title' => 'New Team Meeting',
                'start_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'end_at' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
                'event_type' => 'meeting',
                'status' => 'confirmed',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('calendar_events', [
            'title' => 'New Team Meeting',
            'event_type' => 'meeting',
            'status' => 'confirmed',
        ]);
    }

    public function test_edit_page_renders(): void
    {
        $event = CalendarEvent::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(EditCalendarEvent::class, ['record' => $event->getRouteKey()])
            ->assertSuccessful();
    }

    public function test_can_update_calendar_event(): void
    {
        $event = CalendarEvent::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Original Title',
        ]);

        Livewire::test(EditCalendarEvent::class, ['record' => $event->getRouteKey()])
            ->fillForm([
                'title' => 'Updated Title',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('calendar_events', [
            'id' => $event->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_title_is_required(): void
    {
        Livewire::test(CreateCalendarEvent::class)
            ->fillForm([
                'title' => '',
                'start_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'end_at' => now()->addDay()->addHour()->format('Y-m-d H:i:s'),
            ])
            ->call('create')
            ->assertHasFormErrors(['title' => 'required']);
    }

    public function test_model_relationships(): void
    {
        $event = CalendarEvent::factory()->create([
            'user_id' => $this->user->id,
            'life_area_id' => $this->lifeArea->id,
        ]);

        $this->assertInstanceOf(LifeArea::class, $event->lifeArea);
        $this->assertEquals($this->lifeArea->id, $event->lifeArea->id);
    }

    public function test_upcoming_scope(): void
    {
        $futureEvent = CalendarEvent::factory()->create([
            'user_id' => $this->user->id,
            'start_at' => now()->addDay(),
            'end_at' => now()->addDay()->addHour(),
            'status' => 'confirmed',
        ]);

        $pastEvent = CalendarEvent::factory()->create([
            'user_id' => $this->user->id,
            'start_at' => now()->subDay(),
            'end_at' => now()->subDay()->addHour(),
            'status' => 'confirmed',
        ]);

        $cancelledEvent = CalendarEvent::factory()->create([
            'user_id' => $this->user->id,
            'start_at' => now()->addDays(2),
            'end_at' => now()->addDays(2)->addHour(),
            'status' => 'cancelled',
        ]);

        $upcoming = CalendarEvent::withoutGlobalScopes()->upcoming()->get();

        $this->assertTrue($upcoming->contains($futureEvent));
        $this->assertFalse($upcoming->contains($pastEvent));
        $this->assertFalse($upcoming->contains($cancelledEvent));
    }

    public function test_duration_helper(): void
    {
        $event = CalendarEvent::factory()->create([
            'user_id' => $this->user->id,
            'start_at' => now(),
            'end_at' => now()->addMinutes(90),
        ]);

        $this->assertEquals(90, $event->duration());
    }

    public function test_factory_google_state(): void
    {
        $event = CalendarEvent::factory()->google()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals('google', $event->source);
        $this->assertNotNull($event->google_event_id);
        $this->assertNotNull($event->synced_at);
    }
}
