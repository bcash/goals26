---
name: phpunit-testing
description: "Integration-first testing with PHPUnit, factories, database assertions, and HTTP tests. Activates when writing tests, debugging test failures, creating factories, or when the user mentions test, testing, PHPUnit, assert, factory, coverage, TDD, or test-driven."
---

# PHPUnit Testing — Integration-First

## When to Apply

Activate this skill when:

- Writing new tests (feature or unit)
- Debugging failing tests
- Creating or modifying model factories
- Discussing test strategy or coverage
- Implementing test-driven development

## Testing Philosophy: Integration First

We prefer **integration/feature tests** over unit tests. Feature tests exercise the full stack:
- HTTP requests through middleware → controller → service → model → database
- This catches real bugs that unit tests miss (N+1 queries, missing validation, auth gaps)

Unit tests are reserved for:
- Complex algorithms or pure functions
- Service methods with significant business logic
- Edge cases that are awkward to trigger through HTTP

## Project Test Structure

```
tests/
├── Feature/           # Integration tests (preferred)
│   ├── Filament/      # Filament resource/page tests
│   ├── Services/      # Service layer tests
│   └── Api/           # API endpoint tests
├── Unit/              # Pure logic tests
└── TestCase.php       # Base test class
```

## Creating Tests

```bash
# Feature test (preferred)
php artisan make:test --phpunit TaskServiceTest

# Unit test (only when needed)
php artisan make:test --phpunit --unit TaskTreeCalculationTest
```

## Test Patterns

### Service Layer Tests (Most Common)

```php
namespace Tests\Feature\Services;

use App\Models\Task;
use App\Models\User;
use App\Services\TaskTreeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTreeServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskTreeService $service;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TaskTreeService::class);
        $this->user = User::factory()->create();
    }

    public function test_add_child_creates_subtask_with_correct_depth(): void
    {
        $parent = Task::factory()->create([
            'user_id' => $this->user->id,
            'depth' => 0,
            'is_leaf' => true,
        ]);

        $child = $this->service->addChild($parent, [
            'title' => 'Subtask',
            'status' => 'todo',
            'priority' => 'medium',
        ]);

        $this->assertEquals(1, $child->depth);
        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertTrue($child->is_leaf);

        // Parent is no longer a leaf
        $parent->refresh();
        $this->assertFalse($parent->is_leaf);
    }

    public function test_complete_leaf_propagates_quality_gate(): void
    {
        // ... test that completing all siblings triggers parent quality gate
    }
}
```

### Filament Resource Tests

```php
namespace Tests\Feature\Filament;

use App\Filament\Resources\TaskResource;
use App\Filament\Resources\TaskResource\Pages\ListTasks;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TaskResourceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_can_list_tasks(): void
    {
        Task::factory()->count(3)->create(['user_id' => $this->user->id]);

        Livewire::test(ListTasks::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(Task::all());
    }

    public function test_can_filter_tasks_by_status(): void
    {
        $todo = Task::factory()->create(['user_id' => $this->user->id, 'status' => 'todo']);
        $done = Task::factory()->create(['user_id' => $this->user->id, 'status' => 'done']);

        Livewire::test(ListTasks::class)
            ->filterTable('status', 'todo')
            ->assertCanSeeTableRecords([$todo])
            ->assertCanNotSeeTableRecords([$done]);
    }
}
```

### Database Assertions

```php
// Prefer assertDatabaseHas over manual queries
$this->assertDatabaseHas('tasks', [
    'title' => 'My Task',
    'status' => 'done',
    'user_id' => $this->user->id,
]);

$this->assertDatabaseMissing('tasks', [
    'status' => 'todo',
    'parent_id' => $parent->id,
]);

$this->assertDatabaseCount('tasks', 5);
```

### Factory Usage

```php
// Always use factories, never manual inserts
$task = Task::factory()->create(['status' => 'todo']);

// Use states when available
$task = Task::factory()->leaf()->create();
$task = Task::factory()->withProject()->create();

// Create related records
$project = Project::factory()
    ->has(Task::factory()->count(3))
    ->create();
```

## Key Conventions

### Always Use RefreshDatabase
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MyTest extends TestCase
{
    use RefreshDatabase;
}
```

### Test Method Naming
```php
// Use descriptive snake_case with test_ prefix
public function test_completing_last_sibling_triggers_quality_gate(): void
public function test_deferred_task_creates_opportunity_pipeline_entry(): void
public function test_user_cannot_access_other_users_tasks(): void
```

### Type Declarations on Test Methods
```php
// Always declare void return type
public function test_something(): void { ... }
```

### Test What Matters
- Happy paths: Does the feature work correctly?
- Failure paths: What happens with invalid input?
- Edge cases: Empty collections, null values, boundary conditions
- Authorization: Can users only access their own data?
- Side effects: Does completing a task trigger the right events?

## Running Tests

```bash
# Single test method
php artisan test --filter=test_add_child_creates_subtask

# Single test file
php artisan test tests/Feature/Services/TaskTreeServiceTest.php

# All tests
php artisan test

# With coverage (if configured)
php artisan test --coverage
```

## Common Pitfalls

- Don't test framework code (Laravel validation rules, Eloquent casting) — test YOUR logic
- Don't mock everything — use `RefreshDatabase` and test against real DB (PostgreSQL)
- Don't forget to test tenant isolation (user A can't see user B's data)
- Don't skip testing the sad paths (invalid input, missing records, unauthorized access)
- Remember: PostgreSQL behaves differently from SQLite — always test against PostgreSQL
- A passing test suite doesn't mean the tests are testing the right things
