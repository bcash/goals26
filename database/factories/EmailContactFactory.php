<?php

namespace Database\Factories;

use App\Models\EmailContact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailContactFactory extends Factory
{
    protected $model = EmailContact::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'freescout_customer_id' => fake()->unique()->numberBetween(1, 999999),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'company' => fake()->company(),
            'job_title' => fake()->jobTitle(),
            'contact_type' => fake()->randomElement(['client', 'vendor', 'supplier', 'partner', 'other']),
            'conversation_count' => fake()->numberBetween(0, 50),
            'first_contact_at' => fake()->dateTimeBetween('-1 year', '-1 month'),
            'last_contact_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function client(): static
    {
        return $this->state(fn () => ['contact_type' => 'client']);
    }

    public function vendor(): static
    {
        return $this->state(fn () => ['contact_type' => 'vendor']);
    }
}
