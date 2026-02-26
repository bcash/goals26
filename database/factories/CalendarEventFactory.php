<?php

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    public function definition(): array
    {
        $startAt = fake()->dateTimeBetween('now', '+2 weeks');
        $endAt = (clone $startAt)->modify('+'.fake()->numberBetween(30, 120).' minutes');

        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'location' => fake()->optional()->address(),
            'start_at' => $startAt,
            'end_at' => $endAt,
            'all_day' => false,
            'status' => 'confirmed',
            'event_type' => fake()->randomElement(['meeting', 'rehearsal', 'personal', 'focus', 'other']),
            'source' => 'manual',
        ];
    }

    public function google(): static
    {
        return $this->state(fn () => [
            'source' => 'google',
            'google_event_id' => fake()->uuid(),
            'google_calendar_id' => fake()->email(),
            'synced_at' => now(),
        ]);
    }

    public function allDay(): static
    {
        return $this->state(fn () => [
            'all_day' => true,
        ]);
    }
}
