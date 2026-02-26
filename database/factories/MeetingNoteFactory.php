<?php

namespace Database\Factories;

use App\Models\MeetingNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MeetingNoteFactory extends Factory
{
    protected $model = MeetingNote::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'meeting_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'meeting_type' => fake()->randomElement(['discovery', 'requirements', 'check-in', 'brainstorm', 'review', 'planning', 'retrospective', 'handoff']),
            'client_type' => fake()->randomElement(['external', 'self']),
            'transcription_status' => 'pending',
            'source' => 'manual',
        ];
    }

    public function withTranscript(): static
    {
        return $this->state(fn () => [
            'transcript' => fake()->paragraphs(5, true),
            'summary' => fake()->paragraph(),
            'transcription_status' => 'complete',
            'transcript_received_at' => now(),
            'analysis_completed_at' => now(),
        ]);
    }

    public function fromGranola(): static
    {
        return $this->state(fn () => [
            'source' => 'granola',
            'granola_meeting_id' => fake()->uuid(),
        ]);
    }
}
