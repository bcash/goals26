<?php

namespace Database\Factories;

use App\Models\EmailConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailConversationFactory extends Factory
{
    protected $model = EmailConversation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'freescout_conversation_id' => fake()->unique()->numberBetween(1, 999999),
            'freescout_mailbox_id' => fake()->numberBetween(1, 10),
            'subject' => fake()->sentence(6),
            'preview' => fake()->text(200),
            'status' => fake()->randomElement(['active', 'pending', 'closed']),
            'type' => 'email',
            'assigned_to_name' => fake()->name(),
            'assigned_to_email' => fake()->safeEmail(),
            'thread_count' => fake()->numberBetween(1, 20),
            'importance' => fake()->randomElement(['low', 'normal', 'high']),
            'category' => fake()->randomElement(['support', 'sales', 'general']),
            'first_message_at' => fake()->dateTimeBetween('-1 month', '-1 week'),
            'last_message_at' => fake()->dateTimeBetween('-1 week', 'now'),
            'analysis_status' => 'pending',
            'needs_review' => false,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function needsReview(): static
    {
        return $this->state(fn () => ['needs_review' => true, 'analysis_status' => 'complete']);
    }

    public function analyzed(): static
    {
        return $this->state(fn () => [
            'analysis_status' => 'complete',
            'ai_summary' => fake()->paragraph(),
            'ai_sentiment' => fake()->randomElement(['positive', 'neutral', 'negative']),
            'ai_priority_score' => fake()->numberBetween(1, 10),
        ]);
    }
}
