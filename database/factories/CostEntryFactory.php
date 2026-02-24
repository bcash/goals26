<?php

namespace Database\Factories;

use App\Models\CostEntry;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CostEntry>
 */
class CostEntryFactory extends Factory
{
    protected $model = CostEntry::class;

    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'project_id'       => null,
            'task_id'          => null,
            'description'      => fake()->sentence(4),
            'category'         => fake()->randomElement(['labour', 'compute', 'infrastructure', 'license', 'other']),
            'amount_cents'     => fake()->numberBetween(500, 50000),
            'currency'         => 'USD',
            'duration_minutes' => fake()->optional(0.6)->numberBetween(15, 480),
            'billable'         => true,
            'logged_date'      => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function forProject(Project $project): static
    {
        return $this->state(fn () => [
            'project_id' => $project->id,
            'user_id'    => $project->user_id,
        ]);
    }

    public function forTask(Task $task): static
    {
        return $this->state(fn () => [
            'task_id'    => $task->id,
            'project_id' => $task->project_id,
            'user_id'    => $task->user_id,
        ]);
    }

    public function labour(int $minutes = 60): static
    {
        return $this->state(fn () => [
            'category'         => 'labour',
            'duration_minutes' => $minutes,
        ]);
    }

    public function nonBillable(): static
    {
        return $this->state(fn () => [
            'billable' => false,
        ]);
    }
}
