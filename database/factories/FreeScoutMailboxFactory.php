<?php

namespace Database\Factories;

use App\Models\FreeScoutMailbox;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class FreeScoutMailboxFactory extends Factory
{
    protected $model = FreeScoutMailbox::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'freescout_mailbox_id' => fake()->unique()->numberBetween(1, 999999),
            'name' => fake()->company().' Support',
            'email' => fake()->unique()->safeEmail(),
            'sync_enabled' => true,
            'last_synced_at' => null,
        ];
    }
}
