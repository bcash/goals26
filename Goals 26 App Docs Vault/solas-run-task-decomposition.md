# SOLAS RÚN
### *Task Decomposition & Project Intelligence*
**Technical Reference v1.1**

---

## Overview

This document describes the **Task Decomposition System** — the methodology and technical architecture behind how Solas Rún breaks large goals down into actionable work, processes that work from the bottom up, enforces quality gates before marking parent tasks complete, tracks client meetings and scope, and keeps projects profitable.

The system is built on four interconnected pillars:

| Pillar | Purpose |
|--------|---------|
| **Hierarchical Task Tree** | A self-referential task structure — BHAGs at the root, leaf-level actions at the tips |
| **Decomposition Interview** | A guided questioning process that breaks any task down until it reaches the 2-minute threshold or a clear action |
| **Quality Gates** | Automatic checkpoints that trigger a review before a parent task can be marked complete |
| **Client & Scope Intelligence** | Meeting transcripts, in/out-of-scope tracking, and budget guardrails to keep projects in the profit zone |

---

## Table of Contents

1. [The Methodology](#1-the-methodology)
2. [Updated Task Data Model](#2-updated-task-data-model)
3. [New Tables](#3-new-tables)
4. [Updated Migrations](#4-updated-migrations)
5. [Models & Relationships](#5-models--relationships)
6. [Task Tree Service](#6-task-tree-service)
7. [Decomposition Interview Service](#7-decomposition-interview-service)
8. [Quality Gate Service](#8-quality-gate-service)
9. [Client Meeting & Scope Resource](#9-client-meeting--scope-resource)
10. [Budget & Cost Tracking](#10-budget--cost-tracking)
11. [Filament UI — Task Tree View](#11-filament-ui--task-tree-view)
12. [AI Integration Points](#12-ai-integration-points)

---

## 1. The Methodology

### The Task Tree

Every piece of work in Solas Rún lives in a tree. The root is a **Big Hairy Audacious Goal (BHAG)**. Each node either:

- Has children (it is a **parent task** — not yet actionable on its own), or
- Has no children (it is a **leaf node** — the actual unit of work to be done)

```
BHAG: Launch Solas Rún to 500 users
│
├── Build the product
│   ├── Design the system
│   │   ├── Write the blueprint doc          ← LEAF (done ✅)
│   │   └── Design the data model            ← LEAF (done ✅)
│   ├── Build the backend
│   │   ├── Set up Laravel + Filament        ← LEAF (done ✅)
│   │   └── Write migrations                 ← LEAF (in progress)
│   └── Build the frontend
│       ├── Dashboard widgets                ← LEAF
│       └── AI Studio interface              ← LEAF
│
├── Acquire users
│   ├── Build the landing page              ← LEAF
│   └── Launch outreach campaign
│       ├── Write email sequence             ← LEAF
│       └── Set up social posts             ← LEAF
│
└── Prepare for launch
    ├── Write onboarding docs               ← LEAF
    └── Set up billing                      ← LEAF
```

**The rule:** You never work on a parent task directly. You only work on leaf nodes. Parent tasks are completed automatically when all their children pass their quality gate.

---

#### The Self-as-Client Task Tree

The task tree applies equally to internal life goals. When you are your own client, the BHAG might be:

```
BHAG (Self): Publish my science fiction novel
│
├── Complete the manuscript
│   ├── Outline all three acts                    ← LEAF (done ✅)
│   ├── Write Act 1 (chapters 1–8)               ← IN PROGRESS
│   └── Write Act 2 (chapters 9–18)              ← DEFERRED
│       └── Deferred because: time resource (TV show in production)
│           Revisit: After season wrap in March
│           Estimated value: Personal — completion of 3-year goal
│
└── Get it published
    ├── Research literary agents                  ← SOMEDAY
    └── Write query letter                        ← SOMEDAY
        └── Deferred until manuscript is complete
```

**Key distinction:** For personal goals, "budget" means **personal resources** — time, energy, money, and capability — not just financial budget.

---

### The 2-Minute Test

At every node in the tree, ask: *Can this be done in under 2 minutes?*

- **Yes** → It is already a leaf node. Do it now or schedule it.
- **No** → It must be decomposed further. Run the Decomposition Interview.

This is the core loop. A task is not ready to be scheduled until it passes the 2-minute test or is explicitly flagged as a focused work block (e.g. a 90-minute deep work session is still a leaf — it has a defined scope and output).

---

### Bottom-Up Processing

Work flows **top-down in design, bottom-up in execution**:

1. **Design phase** — Start at the BHAG. Break it down. Keep breaking until all leaves pass the 2-minute test or have a defined output.
2. **Execution phase** — Work only on leaf nodes. Complete them. When all siblings under a parent are done, the **Quality Gate** triggers before the parent is marked complete.
3. **Completion flows upward** — Once a quality gate passes, the parent's completion status updates. If that parent's siblings are also complete, the grandparent's quality gate triggers. And so on up to the root.

---

### Quality Gate Philosophy

Checking off leaf nodes can create a false sense of progress. The quality gate exists to ask: *"Yes, the small things are done — but is the larger thing actually finished to the right standard?"*

A quality gate is a short structured review that fires automatically when the last child of a parent task is completed. It asks a set of questions specific to the type of work, and it must be passed before the parent task is marked done.

---

## 2. Updated Task Data Model

The `tasks` table gets several new columns to support the tree structure, decomposition tracking, and quality gates.

### New Columns

| Column | Type | Purpose |
|--------|------|---------|
| `parent_id` | foreignId, nullable | Self-referential — the parent task, or null if root |
| `depth` | tinyInteger | How deep in the tree (0 = root/BHAG, 1 = child, 2 = grandchild…) |
| `path` | string | Materialized path e.g. `1/4/12/` for fast tree queries |
| `is_leaf` | boolean | True if this node has no children |
| `decomposition_status` | enum | `needs_breakdown`, `ready`, `complete` |
| `two_minute_check` | boolean | Has this passed the 2-minute test? |
| `estimated_cost` | decimal(10,2) | Labour/resource cost estimate for this task |
| `actual_cost` | decimal(10,2) | Actual cost logged against this task |
| `billable` | boolean | Is this task billable to a client? |
| `quality_gate_status` | enum | `not_triggered`, `pending`, `passed`, `failed` |
| `sort_order` | integer | Sibling ordering within a parent |

### `decomposition_status` Values

| Value | Meaning |
|-------|---------|
| `needs_breakdown` | Has children but not all leaves are ready — still being decomposed |
| `ready` | All leaf descendants have passed the 2-minute test — tree is actionable |
| `complete` | All descendants are done and quality gates have passed |

### `quality_gate_status` Values

| Value | Meaning |
|-------|---------|
| `not_triggered` | Children not yet complete |
| `pending` | All children done — gate is open and awaiting review |
| `passed` | Quality gate review completed successfully |
| `failed` | Quality gate review found issues — children need rework |

---

## 3. New Tables

### `task_quality_gates`
A record of every quality gate review — what was checked, what was found, and whether the parent task passed.

### `client_meetings`
Transcripts and summaries of client meetings linked to a project. The source of truth for what was promised, what was discussed, and what was explicitly excluded.

### `meeting_scope_items`
Individual scope decisions extracted from a meeting — each item is tagged as in-scope, out-of-scope, deferred, or an assumption, and can be linked to a task.

### `meeting_done_items`
While not strictly part of task decomposition, done items created from meeting transcripts can be linked back to tasks, providing a feedback loop: the task tree shows what was planned; done items show what was confirmed delivered and what impact it had.

### `project_budgets`
Budget definition for a project — fixed price or hourly, total budget, and tracked spend.

### `time_entries`
Individual time logs against tasks and projects. Powers actual cost calculations and burn rate tracking.

---

## 4. Updated Migrations

### Update `tasks` Table

```bash
php artisan make:migration add_tree_fields_to_tasks_table
```

```php
public function up(): void
{
    Schema::table('tasks', function (Blueprint $table) {
        // Tree structure
        $table->foreignId('parent_id')
              ->nullable()
              ->after('milestone_id')
              ->constrained('tasks')
              ->nullOnDelete();

        $table->unsignedTinyInteger('depth')->default(0)->after('parent_id');
        $table->string('path', 500)->nullable()->after('depth');
        // Path format: "1/4/12/" — root_id/parent_id/this_id/
        // Enables fast subtree queries: WHERE path LIKE '1/4/%'

        $table->boolean('is_leaf')->default(true)->after('path');
        $table->enum('decomposition_status', [
            'needs_breakdown',
            'ready',
            'complete',
        ])->default('needs_breakdown')->after('is_leaf');

        $table->boolean('two_minute_check')->default(false)->after('decomposition_status');

        // Cost tracking
        $table->decimal('estimated_cost', 10, 2)->nullable()->after('time_estimate_minutes');
        $table->decimal('actual_cost', 10, 2)->default(0)->after('estimated_cost');
        $table->boolean('billable')->default(false)->after('actual_cost');

        // Quality gate
        $table->enum('quality_gate_status', [
            'not_triggered',
            'pending',
            'passed',
            'failed',
        ])->default('not_triggered')->after('billable');

        $table->unsignedSmallInteger('sort_order')->default(0)->after('quality_gate_status');

        // Indexes for tree traversal
        $table->index('parent_id');
        $table->index('path');
        $table->index(['user_id', 'status', 'is_leaf']);
    });
}
```

---

### `task_quality_gates`

```bash
php artisan make:migration create_task_quality_gates_table
```

```php
public function up(): void
{
    Schema::create('task_quality_gates', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('task_id')->constrained()->cascadeOnDelete();

        $table->timestamp('triggered_at');      // When the gate auto-fired
        $table->timestamp('reviewed_at')->nullable(); // When the user completed the review

        $table->enum('status', ['pending', 'passed', 'failed'])->default('pending');

        // AI-generated checklist of review questions for this task type
        $table->json('checklist')->nullable();
        // Structure: [{ "question": "...", "answer": null, "passed": null }]

        $table->text('reviewer_notes')->nullable(); // Free-form notes during review
        $table->text('failure_reason')->nullable(); // If failed — what needs rework?

        $table->unsignedSmallInteger('children_completed')->default(0);
        $table->unsignedSmallInteger('children_total')->default(0);

        $table->timestamps();

        $table->index('user_id');
        $table->index('status');
    });
}
```

---

### `client_meetings`

```bash
php artisan make:migration create_client_meetings_table
```

```php
public function up(): void
{
    Schema::create('client_meetings', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('project_id')->constrained()->cascadeOnDelete();

        $table->string('title');
        $table->date('meeting_date');
        $table->enum('meeting_type', [
            'discovery',
            'requirements',
            'check-in',
            'review',
            'handoff',
            'other',
        ])->default('other');

        $table->json('attendees')->nullable();
        // Structure: [{ "name": "...", "role": "..." }]

        $table->longText('transcript')->nullable(); // Raw transcript or notes
        $table->text('summary')->nullable();         // AI or manual summary
        $table->text('decisions')->nullable();       // Key decisions made
        $table->text('action_items')->nullable();    // Immediate follow-ups

        // AI-extracted scope analysis
        $table->text('ai_scope_analysis')->nullable();

        $table->timestamps();

        $table->index('user_id');
        $table->index('project_id');
    });
}
```

---

### `meeting_scope_items`

```bash
php artisan make:migration create_meeting_scope_items_table
```

```php
public function up(): void
{
    Schema::create('meeting_scope_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('meeting_id')
              ->constrained('client_meetings')
              ->cascadeOnDelete();

        $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
        // Linked to a task if this scope item has been actioned

        $table->text('description');

        $table->enum('type', [
            'in-scope',     // Client confirmed this is included
            'out-of-scope', // Client confirmed this is excluded
            'deferred',     // Acknowledged but pushed to a future phase
            'assumption',   // Team assumed this — not explicitly confirmed
            'risk',         // Identified risk to scope or budget
        ]);

        $table->boolean('confirmed_with_client')->default(false);
        $table->text('client_quote')->nullable(); // Direct quote from transcript
        $table->text('notes')->nullable();

        $table->timestamps();

        $table->index('meeting_id');
        $table->index('type');
    });
}
```

---

### `project_budgets`

```bash
php artisan make:migration create_project_budgets_table
```

```php
public function up(): void
{
    Schema::create('project_budgets', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('project_id')->unique()->constrained()->cascadeOnDelete();

        $table->enum('budget_type', ['fixed', 'hourly', 'retainer'])->default('fixed');
        $table->decimal('budget_total', 10, 2)->nullable(); // Total budget in dollars
        $table->decimal('hourly_rate', 8, 2)->nullable();   // If hourly
        $table->decimal('estimated_hours', 8, 2)->nullable();

        // Rolling calculations — updated by BudgetService
        $table->decimal('actual_spend', 10, 2)->default(0);
        $table->decimal('estimated_remaining', 10, 2)->default(0);
        $table->decimal('burn_rate', 8, 2)->default(0); // Avg cost per day

        $table->unsignedTinyInteger('alert_threshold_percent')->default(80);
        // Trigger alert when actual_spend reaches this % of budget_total

        $table->text('notes')->nullable();
        $table->timestamps();
    });
}
```

---

### `time_entries`

```bash
php artisan make:migration create_time_entries_table
```

```php
public function up(): void
{
    Schema::create('time_entries', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();

        $table->string('description')->nullable();
        $table->decimal('hours', 6, 2);
        $table->date('logged_date');
        $table->boolean('billable')->default(true);
        $table->decimal('hourly_rate', 8, 2)->nullable(); // Override project rate if needed
        $table->decimal('cost', 8, 2)->default(0);        // hours × rate

        $table->timestamps();

        $table->index('user_id');
        $table->index(['project_id', 'logged_date']);
        $table->index(['task_id', 'logged_date']);
    });
}
```

---

## 5. Models & Relationships

### Task Model — Updated

```php
// app/Models/Task.php

use App\Traits\HasTenant;

class Task extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id', 'life_area_id', 'project_id', 'goal_id', 'milestone_id',
        'parent_id', 'depth', 'path', 'is_leaf', 'sort_order',
        'title', 'notes', 'status', 'priority',
        'due_date', 'scheduled_date', 'time_estimate_minutes',
        'estimated_cost', 'actual_cost', 'billable',
        'is_daily_action', 'two_minute_check',
        'decomposition_status', 'quality_gate_status',
    ];

    protected $casts = [
        'due_date'          => 'date',
        'scheduled_date'    => 'date',
        'is_daily_action'   => 'boolean',
        'is_leaf'           => 'boolean',
        'billable'          => 'boolean',
        'two_minute_check'  => 'boolean',
    ];

    // ── Tree Relationships ─────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('sort_order');
    }

    public function descendants(): HasMany
    {
        // All tasks whose path starts with this task's path
        return $this->hasMany(Task::class, 'parent_id')
                    ->where('path', 'like', $this->path . '%');
    }

    public function ancestors(): \Illuminate\Support\Collection
    {
        // Walk up the tree using the materialized path
        if (!$this->path) return collect();

        $ids = collect(explode('/', trim($this->path, '/')))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->reject(fn ($id) => $id === $this->id);

        return Task::withoutGlobalScopes()
            ->whereIn('id', $ids)
            ->orderBy('depth')
            ->get();
    }

    // ── Tree State Helpers ─────────────────────────────────────────────

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function isLeaf(): bool
    {
        return $this->is_leaf;
    }

    public function isReadyToSchedule(): bool
    {
        return $this->is_leaf
            && $this->two_minute_check
            && $this->decomposition_status === 'ready';
    }

    public function completionPercent(): int
    {
        $leaves = $this->allLeaves();
        if ($leaves->isEmpty()) return 0;

        $done = $leaves->where('status', 'done')->count();
        return (int) round(($done / $leaves->count()) * 100);
    }

    public function allLeaves(): \Illuminate\Support\Collection
    {
        return Task::where('path', 'like', $this->path . '%')
                   ->where('is_leaf', true)
                   ->get();
    }

    public function siblingsComplete(): bool
    {
        if (!$this->parent_id) return false;

        return Task::where('parent_id', $this->parent_id)
                   ->where('id', '!=', $this->id)
                   ->where('status', '!=', 'done')
                   ->doesntExist();
    }

    // ── Quality Gate ───────────────────────────────────────────────────

    public function qualityGates(): HasMany
    {
        return $this->hasMany(TaskQualityGate::class);
    }

    public function activeQualityGate(): ?TaskQualityGate
    {
        return $this->qualityGates()->where('status', 'pending')->latest()->first();
    }

    // ── Other Relationships ────────────────────────────────────────────

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    public function totalLoggedHours(): float
    {
        return $this->timeEntries()->sum('hours');
    }
}
```

---

### ClientMeeting Model

```php
// app/Models/ClientMeeting.php

use App\Traits\HasTenant;

class ClientMeeting extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id', 'project_id', 'title', 'meeting_date', 'meeting_type',
        'attendees', 'transcript', 'summary', 'decisions',
        'action_items', 'ai_scope_analysis',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'attendees'    => 'array',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeItems(): HasMany
    {
        return $this->hasMany(MeetingScopeItem::class, 'meeting_id');
    }

    public function inScopeItems(): HasMany
    {
        return $this->scopeItems()->where('type', 'in-scope');
    }

    public function outOfScopeItems(): HasMany
    {
        return $this->scopeItems()->where('type', 'out-of-scope');
    }

    public function risks(): HasMany
    {
        return $this->scopeItems()->where('type', 'risk');
    }
}
```

---

### ProjectBudget Model

```php
// app/Models/ProjectBudget.php

use App\Traits\HasTenant;

class ProjectBudget extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id', 'project_id', 'budget_type', 'budget_total',
        'hourly_rate', 'estimated_hours', 'actual_spend',
        'estimated_remaining', 'burn_rate', 'alert_threshold_percent', 'notes',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function percentUsed(): float
    {
        if (!$this->budget_total || $this->budget_total == 0) return 0;
        return round(($this->actual_spend / $this->budget_total) * 100, 1);
    }

    public function isOverBudget(): bool
    {
        return $this->actual_spend > ($this->budget_total ?? PHP_INT_MAX);
    }

    public function isNearAlert(): bool
    {
        return $this->percentUsed() >= $this->alert_threshold_percent;
    }
}
```

---

## 6. Task Tree Service

The `TaskTreeService` handles all tree operations — building the tree, calculating paths, updating `is_leaf` flags, and propagating completion upward.

```php
// app/Services/TaskTreeService.php

namespace App\Services;

use App\Models\Task;

class TaskTreeService
{
    /**
     * Add a child task to a parent.
     * Updates parent's is_leaf flag and recalculates paths.
     */
    public function addChild(Task $parent, array $attributes): Task
    {
        $child = Task::create(array_merge($attributes, [
            'user_id'   => $parent->user_id,
            'parent_id' => $parent->id,
            'depth'     => $parent->depth + 1,
        ]));

        // Parent is no longer a leaf
        if ($parent->is_leaf) {
            $parent->update([
                'is_leaf'               => false,
                'decomposition_status'  => 'needs_breakdown',
                'two_minute_check'      => false,
            ]);
        }

        $this->rebuildPath($child);

        return $child;
    }

    /**
     * Build the materialized path for a task.
     * Path = all ancestor IDs separated by slashes: "1/4/12/"
     */
    public function rebuildPath(Task $task): void
    {
        $path = '';

        if ($task->parent_id) {
            $parent = Task::withoutGlobalScopes()->find($task->parent_id);
            $path   = ($parent?->path ?? '') . $parent?->id . '/';
        }

        $task->update(['path' => $path . $task->id . '/']);
    }

    /**
     * Get the full tree for a root task, nested as a collection.
     */
    public function getTree(Task $root): array
    {
        $allTasks = Task::where('path', 'like', $root->id . '/%')
            ->orWhere('id', $root->id)
            ->orderBy('depth')
            ->orderBy('sort_order')
            ->get()
            ->keyBy('id');

        return $this->nestChildren($root->id, $allTasks);
    }

    private function nestChildren(int $parentId, $allTasks): array
    {
        return $allTasks
            ->where('parent_id', $parentId)
            ->map(fn ($task) => array_merge($task->toArray(), [
                'children' => $this->nestChildren($task->id, $allTasks),
            ]))
            ->values()
            ->toArray();
    }

    /**
     * Mark a leaf task done and propagate up the tree.
     * Triggers quality gate if all siblings are complete.
     */
    public function completeLeaf(Task $task): void
    {
        if (!$task->is_leaf) {
            throw new \LogicException('Only leaf tasks can be directly completed.');
        }

        $task->update(['status' => 'done']);

        $this->propagateUpward($task);
    }

    /**
     * Walk up the tree. If all siblings are done, trigger the parent's quality gate.
     */
    private function propagateUpward(Task $task): void
    {
        if (!$task->parent_id) return;

        $parent   = Task::find($task->parent_id);
        $siblings = Task::where('parent_id', $parent->id)->get();

        $allDone = $siblings->every(fn ($s) => $s->status === 'done');

        if ($allDone) {
            app(QualityGateService::class)->trigger($parent, $siblings->count());
        }
    }

    /**
     * Get all leaf-level tasks that are ready to be scheduled —
     * the actionable work queue for this user.
     */
    public function getActionableLeaves(?int $projectId = null): \Illuminate\Database\Eloquent\Collection
    {
        return Task::where('is_leaf', true)
            ->where('two_minute_check', true)
            ->whereIn('status', ['todo', 'in-progress'])
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->orderBy('priority', 'desc')
            ->orderBy('due_date')
            ->get();
    }

    /**
     * Get all tasks that still need to be broken down —
     * the decomposition queue.
     */
    public function getNeedsBreakdown(): \Illuminate\Database\Eloquent\Collection
    {
        return Task::where('decomposition_status', 'needs_breakdown')
            ->where('is_leaf', true) // Leaves that haven't been validated yet
            ->orderBy('depth', 'desc') // Work from deepest first
            ->get();
    }

    /**
     * Reorder siblings.
     */
    public function reorder(int $parentId, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Task::where('id', $id)->update(['sort_order' => $index]);
        }
    }

    /**
     * Get all tasks in a tree that are deferred,
     * along with their deferral context.
     */
    public function getDeferredBranches(?int $projectId = null): \Illuminate\Database\Eloquent\Collection
    {
        return Task::where('status', 'deferred')
            ->when($projectId, fn ($q) => $q->where('project_id', $projectId))
            ->with(['parent', 'deferredItem'])
            ->orderBy('depth')
            ->get();
    }

    /**
     * Estimate the total potential value of all deferred branches
     * in a project's task tree.
     */
    public function deferredTreeValue(?int $projectId = null): float
    {
        return \App\Models\DeferredItem::where('project_id', $projectId)
            ->whereNotIn('status', ['archived', 'lost'])
            ->sum('estimated_value') ?? 0;
    }
}
```

---

## 7. Decomposition Interview Service

The Decomposition Interview is a guided AI-assisted conversation that helps break a task down. It asks structured questions until every path leads to either a 2-minute task or a defined leaf action.

```php
// app/Services/DecompositionInterviewService.php

namespace App\Services;

use App\Models\Task;
use App\Models\AiInteraction;

class DecompositionInterviewService
{
    public function __construct(protected AiService $ai) {}

    /**
     * Start a decomposition interview for a task.
     * Returns the first question to ask the user.
     */
    public function start(Task $task): array
    {
        $context = $this->buildContext($task);
        $prompt  = $this->buildStartPrompt($task, $context);

        $response = $this->ai->chat($prompt, 'goal-breakdown', [
            'task_id'    => $task->id,
            'task_title' => $task->title,
            'depth'      => $task->depth,
            'ancestors'  => $task->ancestors()->pluck('title')->toArray(),
        ]);

        return [
            'question'     => $response['question'],
            'suggested_subtasks' => $response['suggested_subtasks'] ?? [],
            'is_ready'     => $response['is_ready'] ?? false,
        ];
    }

    /**
     * Process a user's answer during decomposition and return next step.
     */
    public function answer(Task $task, string $userAnswer, array $conversationHistory): array
    {
        $prompt = $this->buildAnswerPrompt($task, $userAnswer, $conversationHistory);

        $response = $this->ai->chat($prompt, 'goal-breakdown');

        // If AI says the task is small enough, flag it
        if ($response['verdict'] === 'ready') {
            $task->update([
                'two_minute_check'      => true,
                'decomposition_status'  => 'ready',
            ]);

            return ['verdict' => 'ready', 'message' => $response['message']];
        }

        // If AI suggests subtasks, return them for user confirmation
        if (!empty($response['suggested_subtasks'])) {
            return [
                'verdict'            => 'needs_children',
                'suggested_subtasks' => $response['suggested_subtasks'],
                'rationale'          => $response['rationale'],
            ];
        }

        // More questions needed
        return [
            'verdict'  => 'continue',
            'question' => $response['question'],
        ];
    }

    /**
     * Accept suggested subtasks and create them as children.
     */
    public function acceptSubtasks(Task $parent, array $subtaskTitles): array
    {
        $treeService = app(TaskTreeService::class);
        $created     = [];

        foreach ($subtaskTitles as $index => $title) {
            $child = $treeService->addChild($parent, [
                'title'                 => $title,
                'status'                => 'todo',
                'priority'              => $parent->priority,
                'life_area_id'          => $parent->life_area_id,
                'project_id'            => $parent->project_id,
                'goal_id'               => $parent->goal_id,
                'sort_order'            => $index,
                'decomposition_status'  => 'needs_breakdown',
                'is_leaf'               => true,
                'billable'              => $parent->billable,
            ]);
            $created[] = $child;
        }

        return $created;
    }

    // ── Prompt Builders ────────────────────────────────────────────────

    private function buildStartPrompt(Task $task, array $context): string
    {
        return <<<PROMPT
You are a project planning assistant helping break down a task using structured decomposition.

TASK TO BREAK DOWN: "{$task->title}"
DEPTH IN TREE: {$task->depth} (0 = top-level BHAG, higher = more detailed)
PARENT CONTEXT: {$context['parent_chain']}
PROJECT: {$context['project']}
GOAL: {$context['goal']}

Your job is to help determine whether this task:
1. Is already small enough to be done in under 2 minutes or in one focused sitting — in which case mark it READY
2. Needs to be broken into subtasks — in which case suggest 2–6 clear subtasks

Ask ONE clarifying question if needed, OR if the task is clear enough, suggest subtasks directly.

Respond ONLY in this JSON format:
{
  "question": "string or null",
  "suggested_subtasks": ["string", "string"] or [],
  "is_ready": true or false,
  "rationale": "brief explanation"
}
PROMPT;
    }

    private function buildAnswerPrompt(Task $task, string $answer, array $history): string
    {
        $historyText = collect($history)
            ->map(fn ($h) => "Q: {$h['question']}\nA: {$h['answer']}")
            ->join("\n\n");

        return <<<PROMPT
You are decomposing the task: "{$task->title}"

CONVERSATION SO FAR:
{$historyText}

USER JUST ANSWERED: "{$answer}"

Based on this, determine:
- If the task is now clearly small enough (under 2 minutes or one focused sitting) → verdict: "ready"
- If the task needs to be split into subtasks → verdict: "needs_children" with suggested_subtasks
- If you need more information → verdict: "continue" with another question

Respond ONLY in this JSON format:
{
  "verdict": "ready" | "needs_children" | "continue",
  "message": "brief message to show user",
  "question": "next question if verdict is continue, otherwise null",
  "suggested_subtasks": ["string"] or [],
  "rationale": "brief explanation"
}
PROMPT;
    }

    private function buildContext(Task $task): array
    {
        $ancestors   = $task->ancestors()->pluck('title')->implode(' → ');
        $project     = $task->project?->name ?? 'No project';
        $goal        = $task->goal?->title ?? 'No linked goal';

        return compact('ancestors', 'project', 'goal', 'parent_chain');
    }
}
```

---

## 8. Quality Gate Service

```php
// app/Services/QualityGateService.php

namespace App\Services;

use App\Models\Task;
use App\Models\TaskQualityGate;
use Filament\Notifications\Notification;

class QualityGateService
{
    public function __construct(protected AiService $ai) {}

    /**
     * Trigger a quality gate for a parent task.
     * Called automatically when all children are marked done.
     */
    public function trigger(Task $task, int $childrenCount): TaskQualityGate
    {
        // Update task status
        $task->update(['quality_gate_status' => 'pending']);

        // Generate AI checklist for this task type
        $checklist = $this->generateChecklist($task);

        $gate = TaskQualityGate::create([
            'user_id'            => $task->user_id,
            'task_id'            => $task->id,
            'triggered_at'       => now(),
            'status'             => 'pending',
            'checklist'          => $checklist,
            'children_completed' => $childrenCount,
            'children_total'     => $childrenCount,
        ]);

        // Notify the user
        Notification::make()
            ->title('Quality gate ready: ' . $task->title)
            ->body('All subtasks complete. Review required before marking done.')
            ->warning()
            ->persistent()
            ->actions([
                \Filament\Notifications\Actions\Action::make('review')
                    ->label('Review Now')
                    ->url(route('filament.admin.pages.quality-gate-review', ['gate' => $gate->id])),
            ])
            ->sendToDatabase(auth()->user());

        return $gate;
    }

    /**
     * Submit a quality gate review.
     */
    public function submitReview(
        TaskQualityGate $gate,
        array $checklistAnswers,
        string $notes,
        bool $passed
    ): void {
        $checklist = collect($gate->checklist)->map(function ($item, $i) use ($checklistAnswers) {
            return array_merge($item, [
                'answer' => $checklistAnswers[$i]['answer'] ?? null,
                'passed' => $checklistAnswers[$i]['passed'] ?? false,
            ]);
        })->toArray();

        $gate->update([
            'status'         => $passed ? 'passed' : 'failed',
            'reviewed_at'    => now(),
            'checklist'      => $checklist,
            'reviewer_notes' => $notes,
        ]);

        $gate->task->update([
            'quality_gate_status' => $passed ? 'passed' : 'failed',
        ]);

        if ($passed) {
            $gate->task->update(['status' => 'done']);
            // Propagate upward — maybe this triggers a grandparent gate
            app(TaskTreeService::class)->propagateUpward($gate->task);
        } else {
            // Gate failed — reopen relevant children
            $this->reopenFailedChildren($gate->task, $notes);
        }
    }

    /**
     * Generate a context-aware review checklist using AI.
     */
    private function generateChecklist(Task $task): array
    {
        $prompt = <<<PROMPT
A project task has just had all its subtasks completed.
Generate a quality review checklist for: "{$task->title}"

Project: {$task->project?->name}
Goal: {$task->goal?->title}
Depth in tree: {$task->depth}
Number of subtasks completed: {$task->children()->count()}

Generate 3–6 specific quality check questions a professional should ask before
declaring this task fully complete. Questions should prevent scope gaps, quality
issues, and missed deliverables.

Respond ONLY as a JSON array:
[
  { "question": "string", "answer": null, "passed": null },
  ...
]
PROMPT;

        $response = $this->ai->chat($prompt, 'freeform');

        return json_decode($response, true) ?? $this->defaultChecklist();
    }

    private function defaultChecklist(): array
    {
        return [
            ['question' => 'Does the output match what was originally requested?', 'answer' => null, 'passed' => null],
            ['question' => 'Has the work been reviewed against the client\'s stated requirements?', 'answer' => null, 'passed' => null],
            ['question' => 'Are there any loose ends or undocumented decisions?', 'answer' => null, 'passed' => null],
            ['question' => 'Has this been tested or verified in context?', 'answer' => null, 'passed' => null],
        ];
    }

    private function reopenFailedChildren(Task $task, string $reason): void
    {
        // Find completed children that may need rework
        Task::where('parent_id', $task->id)
            ->where('status', 'done')
            ->update(['status' => 'todo', 'quality_gate_status' => 'failed']);
    }
}
```

---

## 9. Client Meeting & Scope Resource

**Note:** The ClientMeetingResource form should be replaced with the tabbed version from `solas-run-meeting-intelligence.md` Section 10.

### ClientMeetingResource

```php
// app/Filament/Resources/ClientMeetingResource.php

protected static ?string $model = ClientMeeting::class;
protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
protected static ?string $navigationGroup = 'Goals & Projects';
protected static ?string $navigationLabel = 'Client Meetings';
protected static ?int $navigationSort = 5;

public static function getRelationManagers(): array
{
    return [
        ScopeItemsRelationManager::class,
        DoneItemsRelationManager::class,      // ← NEW
        ResourceSignalsRelationManager::class, // ← NEW
    ];
}

public static function form(Form $form): Form
{
    return $form->schema([
        Section::make('Meeting Details')->schema([
            Grid::make(2)->schema([
                Select::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name')
                    ->required()
                    ->searchable(),

                Select::make('meeting_type')
                    ->options([
                        'discovery'    => '🔍 Discovery',
                        'requirements' => '📋 Requirements',
                        'check-in'     => '✅ Check-in',
                        'review'       => '👀 Review',
                        'handoff'      => '🤝 Handoff',
                        'other'        => '📌 Other',
                    ])
                    ->default('other'),
            ]),

            Grid::make(2)->schema([
                TextInput::make('title')->required(),
                DatePicker::make('meeting_date')->required()->default(today()),
            ]),

            \Filament\Forms\Components\TagsInput::make('attendees')
                ->label('Attendees')
                ->placeholder('Add name or role')
                ->columnSpanFull(),
        ]),

        Section::make('Notes & Transcript')->schema([
            Textarea::make('transcript')
                ->label('Transcript / Raw Notes')
                ->rows(10)
                ->helperText('Paste the full meeting transcript or detailed notes here. AI will analyze for scope.')
                ->columnSpanFull(),

            Textarea::make('summary')
                ->label('Summary')
                ->rows(4)
                ->columnSpanFull(),

            Textarea::make('decisions')
                ->label('Key Decisions')
                ->rows(3)
                ->placeholder('What was decided in this meeting?')
                ->columnSpanFull(),

            Textarea::make('action_items')
                ->label('Action Items')
                ->rows(3)
                ->placeholder('What needs to happen immediately after this meeting?')
                ->columnSpanFull(),
        ]),

        Section::make('AI Scope Analysis')->schema([
            Placeholder::make('ai_scope_analysis')
                ->label('AI Scope Analysis')
                ->content(fn ($record) =>
                    $record?->ai_scope_analysis
                    ?? 'Save the meeting with a transcript to generate AI scope analysis.'
                )
                ->columnSpanFull(),
        ]),
    ]);
}
```

### MeetingScopeItemsRelationManager

```bash
php artisan make:filament-relation-manager ClientMeetingResource scopeItems description
```

```php
public function form(Form $form): Form
{
    return $form->schema([
        Textarea::make('description')
            ->required()
            ->rows(2)
            ->columnSpanFull(),

        Grid::make(2)->schema([
            Select::make('type')
                ->options([
                    'in-scope'     => '✅ In Scope',
                    'out-of-scope' => '🚫 Out of Scope',
                    'deferred'     => '⏸️ Deferred',
                    'assumption'   => '🤔 Assumption',
                    'risk'         => '⚠️ Risk',
                ])
                ->required(),

            Select::make('task_id')
                ->label('Linked Task')
                ->relationship('task', 'title')
                ->searchable()
                ->nullable(),
        ]),

        TextInput::make('client_quote')
            ->label('Direct Client Quote')
            ->helperText('Exact words from the transcript that support this decision')
            ->nullable()
            ->columnSpanFull(),

        Toggle::make('confirmed_with_client')->inline(false),
    ]);
}

public function table(Table $table): Table
{
    return $table->columns([
        TextColumn::make('type')->badge()
            ->color(fn (string $state): string => match($state) {
                'in-scope'     => 'success',
                'out-of-scope' => 'danger',
                'deferred'     => 'warning',
                'assumption'   => 'info',
                'risk'         => 'danger',
                default        => 'gray',
            }),
        TextColumn::make('description')->wrap()->limit(80),
        IconColumn::make('confirmed_with_client')->boolean()->label('Confirmed'),
        TextColumn::make('task.title')->label('Linked Task')->placeholder('—')->limit(30),
    ])
    ->headerActions([\Filament\Tables\Actions\CreateAction::make()])
    ->actions([EditAction::make(), DeleteAction::make()]);
}
```

---

## 10. Budget & Cost Tracking

### BudgetService

```php
// app/Services/BudgetService.php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\TimeEntry;

class BudgetService
{
    /**
     * Recalculate all budget metrics for a project.
     * Called whenever a time entry is created or updated.
     */
    public function recalculate(Project $project): void
    {
        $budget = $project->budget;
        if (!$budget) return;

        $totalHours  = TimeEntry::where('project_id', $project->id)->sum('hours');
        $actualSpend = TimeEntry::where('project_id', $project->id)
                                ->where('billable', true)
                                ->sum('cost');

        $remaining = ($budget->budget_total ?? 0) - $actualSpend;

        // Burn rate: average daily spend over the project lifetime
        $projectAge = max(1, $project->created_at->diffInDays(now()));
        $burnRate   = round($actualSpend / $projectAge, 2);

        $budget->update([
            'actual_spend'         => $actualSpend,
            'estimated_remaining'  => max(0, $remaining),
            'burn_rate'            => $burnRate,
        ]);

        if ($budget->isNearAlert()) {
            $this->sendBudgetAlert($project, $budget);
        }
    }

    /**
     * Log time against a task and trigger budget recalculation.
     */
    public function logTime(
        int $projectId,
        int $taskId,
        float $hours,
        string $description = '',
        bool $billable = true
    ): TimeEntry {
        $project = Project::find($projectId);
        $rate    = $project?->budget?->hourly_rate ?? 0;

        $entry = TimeEntry::create([
            'user_id'     => auth()->id(),
            'project_id'  => $projectId,
            'task_id'     => $taskId,
            'description' => $description,
            'hours'       => $hours,
            'logged_date' => today(),
            'billable'    => $billable,
            'hourly_rate' => $rate,
            'cost'        => round($hours * $rate, 2),
        ]);

        $this->recalculate($project);

        return $entry;
    }

    private function sendBudgetAlert(Project $project, ProjectBudget $budget): void
    {
        \Filament\Notifications\Notification::make()
            ->title('⚠️ Budget Alert: ' . $project->name)
            ->body(
                number_format($budget->percentUsed(), 1) . '% of budget used. ' .
                '$' . number_format($budget->estimated_remaining, 2) . ' remaining.'
            )
            ->warning()
            ->persistent()
            ->sendToDatabase(auth()->user());
    }
}
```

### ProjectBudgetResource

```php
protected static ?string $model = ProjectBudget::class;
protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
protected static ?string $navigationGroup = 'Goals & Projects';
protected static ?string $navigationLabel = 'Budgets';
protected static ?int $navigationSort = 6;

public static function form(Form $form): Form
{
    return $form->schema([
        Section::make('Budget Setup')->schema([
            Grid::make(2)->schema([
                Select::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name')
                    ->required()
                    ->searchable(),

                Select::make('budget_type')
                    ->options([
                        'fixed'    => 'Fixed Price',
                        'hourly'   => 'Hourly',
                        'retainer' => 'Retainer',
                    ])
                    ->live()
                    ->required(),
            ]),

            Grid::make(3)->schema([
                TextInput::make('budget_total')
                    ->label('Total Budget ($)')
                    ->numeric()
                    ->prefix('$'),

                TextInput::make('hourly_rate')
                    ->label('Hourly Rate ($)')
                    ->numeric()
                    ->prefix('$')
                    ->visible(fn ($get) => $get('budget_type') === 'hourly'),

                TextInput::make('estimated_hours')
                    ->label('Estimated Hours')
                    ->numeric()
                    ->suffix('hrs'),
            ]),

            TextInput::make('alert_threshold_percent')
                ->label('Alert When Budget Reaches (%)')
                ->numeric()
                ->default(80)
                ->suffix('%'),

            Textarea::make('notes')->rows(2)->nullable()->columnSpanFull(),
        ]),

        Section::make('Current Status')->schema([
            Grid::make(3)->schema([
                Placeholder::make('actual_spend')
                    ->label('Actual Spend')
                    ->content(fn ($record) => $record ? '$' . number_format($record->actual_spend, 2) : '—'),

                Placeholder::make('percent_used')
                    ->label('% Used')
                    ->content(fn ($record) => $record ? $record->percentUsed() . '%' : '—'),

                Placeholder::make('estimated_remaining')
                    ->label('Remaining')
                    ->content(fn ($record) => $record ? '$' . number_format($record->estimated_remaining, 2) : '—'),
            ]),
        ])->visibleOn('edit'),
    ]);
}
```

---

## 11. Filament UI — Task Tree View

The standard Filament table is not suitable for hierarchical display. Create a custom **Task Tree Page** that renders the tree with indentation and inline decomposition actions.

```bash
php artisan make:filament-page TaskTree
```

```php
// app/Filament/Pages/TaskTree.php

namespace App\Filament\Pages;

use App\Models\Task;
use App\Services\TaskTreeService;
use Filament\Pages\Page;

class TaskTree extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Goals & Projects';
    protected static ?string $navigationLabel = 'Task Tree';
    protected static ?string $title = 'Task Tree';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.pages.task-tree';

    public ?int $selectedProjectId = null;
    public array $tree = [];

    public function mount(): void
    {
        $this->loadTree();
    }

    public function loadTree(): void
    {
        $roots = Task::whereNull('parent_id')
            ->when($this->selectedProjectId, fn ($q) => $q->where('project_id', $this->selectedProjectId))
            ->orderBy('priority', 'desc')
            ->orderBy('sort_order')
            ->get();

        $service    = app(TaskTreeService::class);
        $this->tree = $roots->map(fn ($root) => $service->getTree($root))->toArray();
    }

    public function decompose(int $taskId): void
    {
        $task = Task::findOrFail($taskId);
        $this->redirect(route('filament.admin.pages.decomposition-interview', ['task' => $task->id]));
    }

    public function completeTask(int $taskId): void
    {
        $task = Task::findOrFail($taskId);
        app(TaskTreeService::class)->completeLeaf($task);
        $this->loadTree();
    }
}
```

### Blade View — Task Tree Page

```blade
{{-- resources/views/filament/pages/task-tree.blade.php --}}

<x-filament-panels::page>
    <div class="space-y-1">
        @foreach($tree as $rootNode)
            @include('filament.partials.task-tree-node', ['node' => $rootNode, 'depth' => 0])
        @endforeach
    </div>
</x-filament-panels::page>
```

### Blade Partial — Tree Node (Recursive)

```blade
{{-- resources/views/filament/partials/task-tree-node.blade.php --}}

@php
    $statusColors = [
        'todo'        => 'text-gray-400',
        'in-progress' => 'text-warning-500',
        'done'        => 'text-success-500 line-through',
        'deferred'    => 'text-info-400',
    ];
    $isLeaf   = empty($node['children']);
    $isDone   = $node['status'] === 'done';
    $hasGate  = $node['quality_gate_status'] === 'pending';
@endphp

<div style="padding-left: {{ $depth * 1.5 }}rem" class="py-1">
    <div class="flex items-center gap-2 group rounded-lg px-2 py-1 hover:bg-gray-50 dark:hover:bg-gray-800">

        {{-- Expand/collapse indicator --}}
        @if(!$isLeaf)
            <span class="text-gray-300 w-4 flex-shrink-0">
                @if($depth === 0) 🎯 @else ├ @endif
            </span>
        @else
            <span class="text-gray-200 w-4 flex-shrink-0">└</span>
        @endif

        {{-- Complete button (leaves only) --}}
        @if($isLeaf && !$isDone)
            <button
                wire:click="completeTask({{ $node['id'] }})"
                class="w-4 h-4 rounded border-2 border-gray-300 hover:border-success-500 flex-shrink-0"
            ></button>
        @elseif($isDone)
            <span class="text-success-500 flex-shrink-0">✓</span>
        @else
            <span class="w-4 flex-shrink-0"></span>
        @endif

        {{-- Title --}}
        <span class="text-sm flex-1 {{ $statusColors[$node['status']] ?? 'text-gray-700 dark:text-gray-300' }}">
            {{ $node['title'] }}
        </span>

        {{-- Quality gate badge --}}
        @if($hasGate)
            <span class="text-xs px-2 py-0.5 rounded-full bg-warning-100 text-warning-700 font-semibold">
                ⚠️ Review Required
            </span>
        @endif

        {{-- Decompose button (unready leaves) --}}
        @if($isLeaf && !$node['two_minute_check'] && !$isDone)
            <button
                wire:click="decompose({{ $node['id'] }})"
                class="text-xs text-primary-500 hover:underline opacity-0 group-hover:opacity-100 transition-opacity"
            >
                Break down →
            </button>
        @endif

        {{-- Leaf status badge --}}
        @if($isLeaf && $node['two_minute_check'])
            <span class="text-xs text-gray-400 opacity-0 group-hover:opacity-100">✓ Ready</span>
        @endif
    </div>

    {{-- Recursively render children --}}
    @foreach($node['children'] ?? [] as $child)
        @include('filament.partials.task-tree-node', ['node' => $child, 'depth' => $depth + 1])
    @endforeach
</div>
```

---

## 12. AI Integration Points

This system adds three new AI integration types to `AiService`:

### 1. Decomposition Interview

**When:** User triggers "Break down →" on an unready leaf task.
**What it does:** Asks structured questions to determine whether the task needs children or is already small enough.
**Stores in:** `ai_interactions` with `interaction_type = 'goal-breakdown'`

---

### 2. Quality Gate Checklist Generation

**When:** All children of a parent task are marked done.
**What it does:** Generates a context-aware review checklist of 3–6 questions specific to the type of work.
**Stores in:** `task_quality_gates.checklist`

---

### 3. Scope Analysis from Meeting Transcript

**When:** A client meeting is saved with a transcript.
**What it does:** Reads the transcript and extracts:
- In-scope items explicitly confirmed by the client
- Out-of-scope items explicitly excluded
- Assumptions made by the team
- Risks identified
- Suggested tasks to create from in-scope items

**Note:** This is replaced by the full `MeetingIntelligenceService::analyze()` method from `solas-run-meeting-intelligence.md` Section 8.

**Prompt template:**

```php
public function analyzeMeetingScope(ClientMeeting $meeting): string
{
    $prompt = <<<PROMPT
Analyze the following client meeting transcript and extract scope intelligence.

PROJECT: {$meeting->project->name}
MEETING TYPE: {$meeting->meeting_type}
DATE: {$meeting->meeting_date->format('M j, Y')}

TRANSCRIPT:
{$meeting->transcript}

Extract and categorize the following:
1. IN-SCOPE items — things the client explicitly said are included
2. OUT-OF-SCOPE items — things the client explicitly excluded
3. ASSUMPTIONS — things the team assumed but the client didn't confirm
4. RISKS — anything that could jeopardize budget, timeline, or quality
5. ACTION ITEMS — concrete next steps mentioned

For each item, include a direct quote from the transcript if possible.

Format your response as a structured summary a project manager would use
to define project boundaries and protect the budget.
PROMPT;

    return $this->chat($prompt, 'freeform', [
        'meeting_id' => $meeting->id,
        'project_id' => $meeting->project_id,
    ]);
}
```

---

### Scope Creep Guard

When a new task is being created inside a project that has client meetings, the AI can optionally check whether the new task is in or out of scope:

```php
public function checkTaskScope(Task $task, Project $project): array
{
    $scopeItems = $project->meetings()
        ->with('scopeItems')
        ->get()
        ->flatMap(fn ($m) => $m->scopeItems)
        ->map(fn ($item) => "[{$item->type}] {$item->description}")
        ->join("\n");

    $prompt = <<<PROMPT
Given the following scope definitions for project "{$project->name}":

{$scopeItems}

Is this new task IN SCOPE or OUT OF SCOPE?
Task: "{$task->title}"

Respond as JSON: { "verdict": "in-scope"|"out-of-scope"|"unclear", "reason": "..." }
PROMPT;

    $response = $this->chat($prompt, 'freeform');
    return json_decode($response, true) ?? ['verdict' => 'unclear', 'reason' => ''];
}
```

**Add to checkTaskScope() — internal version:**

```php
if ($project->client_type === 'self') {
    // For internal goals, scope creep = taking on more than
    // current personal resources allow
    $resourceStatus = $this->assessPersonalResources();
    // ... check if new task is within current capacity
}
```

---

*Solas Rún • Version 1.1 • Task Decomposition & Project Intelligence*
