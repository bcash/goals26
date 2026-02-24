<?php

namespace Tests\Feature;

use App\Models\CostEntry;
use App\Models\LifeArea;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\CostEntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Money\Money;
use Tests\TestCase;

class CostEntryTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private LifeArea $lifeArea;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $this->lifeArea = LifeArea::create([
            'user_id' => $this->user->id,
            'name' => 'Business',
        ]);

        $this->project = Project::create([
            'user_id' => $this->user->id,
            'life_area_id' => $this->lifeArea->id,
            'name' => 'Test Project',
            'status' => 'active',
            'budget_cents' => 500000,
            'budget_currency' => 'USD',
        ]);
    }

    // ── Model & Relationships ───────────────────────────────────────

    public function test_cost_entry_can_be_created_with_factory(): void
    {
        $entry = CostEntry::factory()->forProject($this->project)->create();

        $this->assertDatabaseHas('cost_entries', [
            'id' => $entry->id,
            'project_id' => $this->project->id,
        ]);
    }

    public function test_cost_entry_belongs_to_project(): void
    {
        $entry = CostEntry::factory()->forProject($this->project)->create();

        $this->assertInstanceOf(Project::class, $entry->project);
        $this->assertEquals($this->project->id, $entry->project->id);
    }

    public function test_cost_entry_belongs_to_task(): void
    {
        $task = Task::create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'title' => 'Test Task',
            'status' => 'todo',
            'priority' => 'medium',
            'is_leaf' => true,
        ]);

        $entry = CostEntry::factory()->forTask($task)->create();

        $this->assertInstanceOf(Task::class, $entry->task);
        $this->assertEquals($task->id, $entry->task->id);
    }

    public function test_project_has_many_cost_entries(): void
    {
        CostEntry::factory()->count(3)->forProject($this->project)->create();

        $this->assertCount(3, $this->project->costEntries);
    }

    public function test_task_has_many_cost_entries(): void
    {
        $task = Task::create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'title' => 'Test Task',
            'status' => 'todo',
            'priority' => 'medium',
            'is_leaf' => true,
        ]);

        CostEntry::factory()->count(2)->forTask($task)->create();

        $this->assertCount(2, $task->costEntries);
    }

    // ── MoneyCast Integration ───────────────────────────────────────

    public function test_amount_cents_is_cast_to_money_object(): void
    {
        $entry = CostEntry::factory()->forProject($this->project)->create([
            'amount_cents' => 12550,
            'currency' => 'USD',
        ]);

        $entry->refresh();

        $this->assertInstanceOf(Money::class, $entry->amount_cents);
        $this->assertEquals('12550', $entry->amount_cents->getAmount());
        $this->assertEquals('USD', $entry->amount_cents->getCurrency()->getCode());
    }

    public function test_project_budget_cents_is_cast_to_money_object(): void
    {
        $this->project->refresh();

        $this->assertInstanceOf(Money::class, $this->project->budget_cents);
        $this->assertEquals('500000', $this->project->budget_cents->getAmount());
        $this->assertEquals('USD', $this->project->budget_cents->getCurrency()->getCode());
    }

    public function test_amount_in_dollars_helper(): void
    {
        $entry = CostEntry::factory()->forProject($this->project)->create([
            'amount_cents' => 12550,
        ]);

        $entry->refresh();

        $this->assertEquals(125.50, $entry->amountInDollars());
    }

    // ── CostEntryService ────────────────────────────────────────────

    public function test_service_create_persists_entry(): void
    {
        $service = new CostEntryService;

        $entry = $service->create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'description' => 'Server hosting',
            'category' => 'infrastructure',
            'amount_cents' => 9900,
            'currency' => 'USD',
            'billable' => true,
            'logged_date' => today(),
        ]);

        $this->assertDatabaseHas('cost_entries', [
            'id' => $entry->id,
            'description' => 'Server hosting',
            'amount_cents' => 9900,
        ]);
    }

    public function test_service_total_for_project(): void
    {
        $service = new CostEntryService;

        CostEntry::factory()->forProject($this->project)->create(['amount_cents' => 10000, 'billable' => true]);
        CostEntry::factory()->forProject($this->project)->create(['amount_cents' => 5000, 'billable' => true]);
        CostEntry::factory()->forProject($this->project)->create(['amount_cents' => 3000, 'billable' => false]);

        $total = $service->totalForProject($this->project);
        $this->assertInstanceOf(Money::class, $total);
        $this->assertEquals('18000', $total->getAmount());

        $billableOnly = $service->totalForProject($this->project, billable: true);
        $this->assertEquals('15000', $billableOnly->getAmount());
    }

    public function test_service_total_for_project_by_category(): void
    {
        $service = new CostEntryService;

        CostEntry::factory()->forProject($this->project)->create(['amount_cents' => 10000, 'category' => 'labour']);
        CostEntry::factory()->forProject($this->project)->create(['amount_cents' => 5000, 'category' => 'compute']);
        CostEntry::factory()->forProject($this->project)->create(['amount_cents' => 3000, 'category' => 'labour']);

        $labourTotal = $service->totalForProject($this->project, category: 'labour');
        $this->assertEquals('13000', $labourTotal->getAmount());
    }

    public function test_service_total_for_task(): void
    {
        $service = new CostEntryService;
        $task = Task::create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'title' => 'Test Task',
            'status' => 'todo',
            'priority' => 'medium',
            'is_leaf' => true,
        ]);

        CostEntry::factory()->forTask($task)->create(['amount_cents' => 7500]);
        CostEntry::factory()->forTask($task)->create(['amount_cents' => 2500]);

        $total = $service->totalForTask($task);
        $this->assertEquals('10000', $total->getAmount());
    }

    public function test_service_total_minutes_for_task(): void
    {
        $service = new CostEntryService;
        $task = Task::create([
            'user_id' => $this->user->id,
            'project_id' => $this->project->id,
            'title' => 'Test Task',
            'status' => 'todo',
            'priority' => 'medium',
            'is_leaf' => true,
        ]);

        CostEntry::factory()->forTask($task)->labour(120)->create();
        CostEntry::factory()->forTask($task)->labour(60)->create();

        $this->assertEquals(180, $service->totalMinutesForTask($task));
    }

    public function test_service_budget_remaining(): void
    {
        $service = new CostEntryService;

        CostEntry::factory()->forProject($this->project)->create(['amount_cents' => 100000, 'billable' => true]);
        CostEntry::factory()->forProject($this->project)->create(['amount_cents' => 50000, 'billable' => true]);
        CostEntry::factory()->forProject($this->project)->create(['amount_cents' => 20000, 'billable' => false]);

        $remaining = $service->budgetRemaining($this->project);
        $this->assertInstanceOf(Money::class, $remaining);
        $this->assertEquals('350000', $remaining->getAmount());
    }

    public function test_service_budget_utilization(): void
    {
        $service = new CostEntryService;

        CostEntry::factory()->forProject($this->project)->create(['amount_cents' => 250000, 'billable' => true]);

        $this->assertEquals(50.0, $service->budgetUtilization($this->project));
    }

    public function test_service_budget_utilization_returns_zero_for_no_budget(): void
    {
        $service = new CostEntryService;

        $noBudgetProject = Project::create([
            'user_id' => $this->user->id,
            'life_area_id' => $this->lifeArea->id,
            'name' => 'No Budget Project',
            'status' => 'active',
        ]);

        $this->assertEquals(0.0, $service->budgetUtilization($noBudgetProject));
    }

    public function test_service_is_over_budget(): void
    {
        $service = new CostEntryService;

        CostEntry::factory()->forProject($this->project)->create(['amount_cents' => 500001, 'billable' => true]);

        $this->assertTrue($service->isOverBudget($this->project));
    }

    public function test_service_is_not_over_budget(): void
    {
        $service = new CostEntryService;

        CostEntry::factory()->forProject($this->project)->create(['amount_cents' => 400000, 'billable' => true]);

        $this->assertFalse($service->isOverBudget($this->project));
    }

    public function test_service_is_over_budget_returns_false_for_no_budget(): void
    {
        $service = new CostEntryService;

        $noBudgetProject = Project::create([
            'user_id' => $this->user->id,
            'life_area_id' => $this->lifeArea->id,
            'name' => 'No Budget Project',
            'status' => 'active',
        ]);

        $this->assertFalse($service->isOverBudget($noBudgetProject));
    }

    // ── Factory States ──────────────────────────────────────────────

    public function test_factory_non_billable_state(): void
    {
        $entry = CostEntry::factory()->forProject($this->project)->nonBillable()->create();

        $this->assertFalse($entry->billable);
    }

    public function test_factory_labour_state(): void
    {
        $entry = CostEntry::factory()->forProject($this->project)->labour(90)->create();

        $this->assertEquals('labour', $entry->category);
        $this->assertEquals(90, $entry->duration_minutes);
    }
}
