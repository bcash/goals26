<?php

namespace Database\Factories;

use App\Models\EmailConversation;
use App\Models\EmailThread;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailThreadFactory extends Factory
{
    protected $model = EmailThread::class;

    public function definition(): array
    {
        return [
            'email_conversation_id' => EmailConversation::factory(),
            'freescout_thread_id' => fake()->unique()->numberBetween(1, 999999),
            'type' => fake()->randomElement(['customer', 'agent']),
            'body' => fake()->paragraphs(2, true),
            'from_name' => fake()->name(),
            'from_email' => fake()->safeEmail(),
            'has_attachments' => false,
            'attachment_count' => 0,
            'message_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function fromCustomer(): static
    {
        return $this->state(fn () => ['type' => 'customer']);
    }

    public function fromAgent(): static
    {
        return $this->state(fn () => ['type' => 'agent']);
    }

    public function note(): static
    {
        return $this->state(fn () => ['type' => 'note']);
    }

    public function scored(): static
    {
        return $this->state(fn () => [
            'type' => 'agent',
            'ai_quality_score' => fake()->numberBetween(1, 10),
            'ai_quality_notes' => fake()->sentence(),
        ]);
    }
}
