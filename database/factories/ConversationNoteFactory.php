<?php

namespace Database\Factories;

use App\Models\ConversationNote;
use App\Models\EmailConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConversationNoteFactory extends Factory
{
    protected $model = ConversationNote::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'email_conversation_id' => EmailConversation::factory(),
            'content' => fake()->paragraph(),
            'note_type' => fake()->randomElement(['observation', 'action_proposal', 'response_draft', 'quality_feedback']),
        ];
    }

    public function responseDraft(): static
    {
        return $this->state(fn () => ['note_type' => 'response_draft']);
    }

    public function synced(): static
    {
        return $this->state(fn () => [
            'freescout_thread_id' => fake()->numberBetween(1, 999999),
            'synced_at' => now(),
        ]);
    }
}
