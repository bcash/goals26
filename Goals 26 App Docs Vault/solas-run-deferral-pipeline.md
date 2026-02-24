# SOLAS RÚN
### *GTD Deferral & Opportunity Pipeline*
**Technical Reference v1.1**

---

## Overview

In most productivity systems, a deferred task is a dead end — something pushed aside and forgotten. In Solas Rún, **deferral is the beginning of a pipeline**.

Every task, feature, or idea that is deliberately set aside becomes a structured asset: a future sales opportunity, a phase 2 proposal seed, an upgrade conversation starter, or a recurring revenue trigger. The system captures *why* something was deferred, *who* it matters to, and *when* it should be revisited — transforming your deferred backlog into one of the most valuable parts of your business.

This is GTD done completely — not just the trusted system for getting things done today, but the trusted system for knowing what to do *next quarter, next year, and in the next client conversation*.

---

## Table of Contents

1. [The GTD Deferral Philosophy](#1-the-gtd-deferral-philosophy)
2. [Deferral Types & Taxonomy](#2-deferral-types--taxonomy)
3. [New & Updated Tables](#3-new--updated-tables)
4. [Updated Migrations](#4-updated-migrations)
5. [Models & Relationships](#5-models--relationships)
6. [Deferral Service](#6-deferral-service)
7. [Opportunity Pipeline Service](#7-opportunity-pipeline-service)
8. [Filament Resources](#8-filament-resources)
9. [AI Integration Points](#9-ai-integration-points)
10. [The Review Cadence](#10-the-review-cadence)
11. [Dashboard Widget — Opportunity Pipeline](#11-dashboard-widget--opportunity-pipeline)

---

## 1. The GTD Deferral Philosophy

### The Four Outcomes of Any Task

When a task or idea is processed in GTD, it has exactly four possible outcomes:

| Outcome | What it means | What Solas Rún does |
|---------|--------------|---------------------|
| **Do it now** | Under 2 minutes or the right moment | Assigned to today's plan |
| **Delegate it** | Someone else should own this | Assigned to a team member or client |
| **Defer it** | Right idea, wrong time | Captured in the Deferral Pipeline |
| **Delete it** | Not worth pursuing ever | Archived with a reason |

Most systems handle the first two well. Solas Rún is built to make the third one *valuable* and the fourth one *intentional*.

---

### Why Deferred Items Are Opportunities

When a client says *"not this time"* or when you decide *"we can't do this in this budget"*, what's really happening is:

- The client **acknowledged the value** of the idea
- The client **doesn't have the capacity** right now — budget, time, bandwidth, or readiness
- The idea is **still on the table** — it just needs the right moment

That moment can be engineered. When you capture the deferral properly — with context, with the client's language, with a revisit date — you transform a "no for now" into a **scheduled future yes**.

```
Deferred Item: "Migrate the site to a headless CMS"
Client said: "Love the idea but not in this budget cycle"
Revisit: Q1 next year, after their budget resets
Trigger: When we're wrapping up the current project
→ This is a $12,000 phase 2 opportunity sitting in your pipeline right now.
```

---

### The Deferral Landscape

Over time, your deferral backlog becomes a rich landscape of intelligence:

- **Upgrade opportunities** — features a current client wanted but couldn't afford
- **Phase 2 proposals** — natural extensions of completed projects
- **Product roadmap seeds** — patterns across multiple clients pointing to features worth building
- **Future sales triggers** — items tied to specific dates, milestones, or client events
- **Recurring revenue hooks** — ongoing needs that were deferred but not eliminated

---

#### The Personal Resource Pipeline

When *you* are the client, the deferral taxonomy is the same — but the resource constraints are internal rather than financial. A personal goal might be deferred because:

| Personal Resource | Example |
|-----------------|---------|
| **Time** | "I want to learn piano but I'm producing a TV show right now" |
| **Money** | "I want to invest in that course but not this quarter" |
| **Technology** | "I need better recording equipment before I can start the podcast" |
| **Capability** | "I want to build the app but I need to learn the framework first" |
| **Energy** | "That's a great goal but I'm already at capacity this season" |
| **Readiness** | "I'm not in the right headspace to tackle that yet" |
| **Dependency** | "I can't start the book tour planning until the manuscript is done" |

These are not excuses — they are **resource signals**. The system captures them, tracks when the constraint is likely to lift, and resurfaces the goal at the right moment.

---

## 2. Deferral Types & Taxonomy

Every deferred item is classified by both its **reason** and its **opportunity type**.

### Deferral Reasons

| Reason | External Use | Internal / Self Use |
|--------|-------------|---------------------|
| `budget` | Client can't afford it | You don't have the money yet |
| `timeline` | Wrong phase of the project | Wrong season of your life |
| `priority` | Lower than current work | Lower than current life demands |
| `client-not-ready` | Client milestone not reached | You haven't developed the prerequisite yet |
| `scope-control` | Protecting project budget | Protecting your energy and focus |
| `awaiting-decision` | Stakeholder undecided | You haven't made up your mind yet |
| `technology` | Platform not ready | Tool, system, or capability not yet built |
| `personal` | Personal circumstances | (Same) |

---

### Opportunity Types

| Type | Description | Revenue Potential |
|------|-------------|-------------------|
| `phase-2` | Direct continuation of current project | High — relationship already established |
| `upsell` | Enhancement to something already delivered | Medium-High — client already invested |
| `upgrade` | Replacing something with a better version | Medium — requires convincing |
| `new-project` | Entirely new engagement triggered by deferred idea | High — but requires proposal |
| `retainer` | Ongoing need that could become a recurring engagement | Very High — recurring revenue |
| `product-feature` | Repeated ask across multiple clients → build it once | Strategic |
| `personal-goal` | Deferred personal ambition — not revenue, but important | Life value |
| `personal-development` | A skill, capability, or experience you want to develop when resourced | Life value |
| `none` | No commercial opportunity — genuinely just deferred | — |

---

## 3. New & Updated Tables

### Updates to `tasks`

Add deferral metadata to the existing tasks table.

### New Table: `deferred_items`

A dedicated table for items that have been formally deferred — richer than a task status, closer to a CRM opportunity record.

**v1.1 Update:** Add `client_type` column (`external` | `self`), `resource_requirements` JSON column, and `resource_check_done` boolean.

### New Table: `opportunity_pipeline`

Tracks the commercial lifecycle of a deferred item — from initial capture through proposal, negotiation, and close.

### New Table: `deferral_reviews`

A log of every time a deferred item was reviewed — keeping the Someday/Maybe list alive instead of stagnant.

---

## 4. Updated Migrations

### Update `tasks` — Add Deferral Fields

```bash
php artisan make:migration add_deferral_fields_to_tasks_table
```

```php
public function up(): void
{
    Schema::table('tasks', function (Blueprint $table) {
        $table->enum('deferral_reason', [
            'budget',
            'timeline',
            'priority',
            'client-not-ready',
            'scope-control',
            'awaiting-decision',
            'technology',
            'personal',
        ])->nullable()->after('quality_gate_status');

        $table->text('deferral_note')->nullable()->after('deferral_reason');
        // Context captured at the moment of deferral — the client's exact words,
        // the reason this was set aside, and why it still matters

        $table->date('revisit_date')->nullable()->after('deferral_note');
        // When should this item surface again for review?

        $table->string('deferral_trigger')->nullable()->after('revisit_date');
        // What event should trigger revisiting?
        // e.g. "After current project launches", "When client's Q1 budget opens"

        $table->boolean('has_opportunity')->default(false)->after('deferral_trigger');
        // Has this been promoted to the Opportunity Pipeline?

        $table->index(['user_id', 'status', 'revisit_date']);
    });
}
```

---

### Create `deferred_items`

```bash
php artisan make:migration create_deferred_items_table
```

```php
public function up(): void
{
    Schema::create('deferred_items', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();

        // Source — where did this deferred item come from?
        $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('meeting_id')
              ->nullable()
              ->constrained('client_meetings')
              ->nullOnDelete();
        $table->foreignId('scope_item_id')
              ->nullable()
              ->constrained('meeting_scope_items')
              ->nullOnDelete();

        $table->string('title');
        $table->text('description')->nullable();

        // Context captured at moment of deferral
        $table->text('client_context')->nullable();
        // What the client said, how they responded, why it matters to them

        $table->text('why_it_matters')->nullable();
        // Your own note: why this is worth keeping and revisiting

        $table->string('client_name')->nullable();
        $table->string('client_quote')->nullable();
        // Exact words from the client that show interest

        // Classification
        $table->enum('deferral_reason', [
            'budget', 'timeline', 'priority',
            'client-not-ready', 'scope-control',
            'awaiting-decision', 'technology', 'personal',
        ])->default('budget');

        $table->enum('opportunity_type', [
            'phase-2', 'upsell', 'upgrade', 'new-project',
            'retainer', 'product-feature', 'personal-goal', 'personal-development', 'none',
        ])->default('none');

        // Client type and resource requirements
        $table->enum('client_type', ['external', 'self'])->default('external');
        $table->json('resource_requirements')->nullable();
        // e.g. { "time": 20, "money": 1500, "capability": "JavaScript", "energy": "medium" }

        $table->boolean('resource_check_done')->default(false);

        // Revenue estimate
        $table->decimal('estimated_value', 10, 2)->nullable();
        $table->string('value_notes')->nullable();
        // e.g. "Estimate based on similar project at $8k"

        // Lifecycle
        $table->enum('status', [
            'someday',      // In the Someday/Maybe list — no date yet
            'scheduled',    // Has a revisit date
            'in-review',    // Currently being reconsidered
            'promoted',     // Moved to active Opportunity Pipeline
            'proposed',     // Proposal sent to client
            'won',          // Became a real project
            'lost',         // Revisited and client declined
            'archived',     // No longer relevant
        ])->default('someday');

        $table->date('deferred_on');
        $table->date('revisit_date')->nullable();
        $table->string('revisit_trigger')->nullable();
        // Event-based trigger: "After project X launches"

        $table->timestamp('last_reviewed_at')->nullable();
        $table->unsignedSmallInteger('review_count')->default(0);

        // AI analysis
        $table->text('ai_opportunity_analysis')->nullable();

        $table->timestamps();

        $table->index('user_id');
        $table->index(['user_id', 'status']);
        $table->index(['user_id', 'revisit_date']);
        $table->index('opportunity_type');
    });
}
```

---

### Create `opportunity_pipeline`

```bash
php artisan make:migration create_opportunity_pipeline_table
```

```php
public function up(): void
{
    Schema::create('opportunity_pipeline', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('deferred_item_id')
              ->constrained('deferred_items')
              ->cascadeOnDelete();
        $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
        // Linked to the resulting project if won

        $table->string('title');
        $table->text('description')->nullable();
        $table->string('client_name');
        $table->string('client_email')->nullable();

        $table->enum('stage', [
            'identified',   // Captured from deferral
            'qualifying',   // Assessing fit and timing
            'nurturing',    // Staying in touch until the time is right
            'proposing',    // Proposal being prepared or sent
            'negotiating',  // In active discussion
            'closed-won',   // Became a real engagement
            'closed-lost',  // Permanently declined
        ])->default('identified');

        $table->decimal('estimated_value', 10, 2)->nullable();
        $table->decimal('actual_value', 10, 2)->nullable();
        // Filled in when closed-won

        $table->unsignedTinyInteger('probability_percent')->default(20);
        // Weighted pipeline value = estimated_value × probability_percent / 100

        $table->date('expected_close_date')->nullable();
        $table->date('actual_close_date')->nullable();

        $table->text('next_action')->nullable();
        $table->date('next_action_date')->nullable();

        $table->text('notes')->nullable();
        $table->text('lost_reason')->nullable();

        $table->timestamps();

        $table->index('user_id');
        $table->index(['user_id', 'stage']);
        $table->index(['user_id', 'next_action_date']);
    });
}
```

---

### Create `deferral_reviews`

```bash
php artisan make:migration create_deferral_reviews_table
```

```php
public function up(): void
{
    Schema::create('deferral_reviews', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained()->cascadeOnDelete();
        $table->foreignId('deferred_item_id')
              ->constrained('deferred_items')
              ->cascadeOnDelete();

        $table->date('reviewed_on');

        $table->enum('outcome', [
            'keep-someday',   // Still valid, no date yet
            'reschedule',     // Set a new revisit date
            'promote',        // Move to Opportunity Pipeline
            'propose',        // Ready to send a proposal
            'archive',        // No longer relevant
        ]);

        $table->date('next_revisit_date')->nullable();
        $table->text('review_notes')->nullable();
        $table->string('context_update')->nullable();
        // Any new information that emerged since last review

        $table->timestamps();

        $table->index(['deferred_item_id', 'reviewed_on']);
    });
}
```

---

## 5. Models & Relationships

### DeferredItem Model

```php
// app/Models/DeferredItem.php

namespace App\Models;

use App\Traits\HasTenant;

class DeferredItem extends Model
{
    use HasTenant;

    protected $fillable = [
        'user_id', 'task_id', 'project_id', 'meeting_id', 'scope_item_id',
        'title', 'description', 'client_context', 'why_it_matters',
        'client_name', 'client_quote',
        'deferral_reason', 'opportunity_type', 'client_type',
        'estimated_value', 'value_notes',
        'status', 'deferred_on', 'revisit_date', 'revisit_trigger',
        'last_reviewed_at', 'review_count', 'ai_opportunity_analysis',
        'resource_requirements', 'resource_check_done',
    ];

    protected $casts = [
        'deferred_on'           => 'date',
        'revisit_date'          => 'date',
        'last_reviewed_at'      => 'datetime',
        'resource_requirements' => 'json',
    ];

    // ── Relationships ──────────────────────────────────────────────────

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(ClientMeeting::class, 'meeting_id');
    }

    public function opportunity(): HasOne
    {
        return $this->hasOne(OpportunityPipeline::class, 'deferred_item_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(DeferralReview::class, 'deferred_item_id')
                    ->orderByDesc('reviewed_on');
    }

    // ── Scopes ────────────────────────────────────────────────────────

    public function scopeDueForReview($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'scheduled')
              ->where('revisit_date', '<=', today());
        })->orWhere(function ($q) {
            $q->where('status', 'someday')
              ->where(function ($inner) {
                  // Someday items resurface in the weekly review every 30 days
                  $inner->whereNull('last_reviewed_at')
                        ->orWhere('last_reviewed_at', '<=', now()->subDays(30));
              });
        });
    }

    public function scopeHasCommercialValue($query)
    {
        return $query->whereNotIn('opportunity_type', ['none', 'personal-goal'])
                     ->whereIn('status', ['someday', 'scheduled', 'in-review', 'promoted']);
    }

    public function scopeByStage($query, string $stage)
    {
        return $query->whereHas('opportunity', fn ($q) => $q->where('stage', $stage));
    }

    // ── Helpers ───────────────────────────────────────────────────────

    public function weightedValue(): float
    {
        if (!$this->opportunity || !$this->estimated_value) return 0;
        return $this->estimated_value * ($this->opportunity->probability_percent / 100);
    }

    public function isOverdue(): bool
    {
        return $this->revisit_date && $this->revisit_date->isPast()
            && in_array($this->status, ['someday', 'scheduled']);
    }

    public function promote(): OpportunityPipeline
    {
        $this->update(['status' => 'promoted', 'has_opportunity' => true]);

        return OpportunityPipeline::create([
            'user_id'          => $this->user_id,
            'deferred_item_id' => $this->id,
            'title'            => $this->title,
            'description'      => $this->description,
            'client_name'      => $this->client_name ?? '',
            'estimated_value'  => $this->estimated_value,
            'stage'            => 'identified',
        ]);
    }
}
```

---

### OpportunityPipeline Model

```php
// app/Models/OpportunityPipeline.php

namespace App\Models;

use App\Traits\HasTenant;

class OpportunityPipeline extends Model
{
    use HasTenant;

    protected $table = 'opportunity_pipeline';

    protected $fillable = [
        'user_id', 'deferred_item_id', 'project_id',
        'title', 'description', 'client_name', 'client_email',
        'stage', 'estimated_value', 'actual_value',
        'probability_percent', 'expected_close_date', 'actual_close_date',
        'next_action', 'next_action_date', 'notes', 'lost_reason',
    ];

    protected $casts = [
        'expected_close_date' => 'date',
        'actual_close_date'   => 'date',
        'next_action_date'    => 'date',
    ];

    public function deferredItem(): BelongsTo
    {
        return $this->belongsTo(DeferredItem::class, 'deferred_item_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function weightedValue(): float
    {
        return ($this->estimated_value ?? 0) * ($this->probability_percent / 100);
    }

    public function totalPipelineValue(): float
    {
        return static::where('user_id', auth()->id())
            ->whereNotIn('stage', ['closed-won', 'closed-lost'])
            ->get()
            ->sum(fn ($o) => $o->weightedValue());
    }
}
```

---

## 6. Deferral Service

The `DeferralService` handles the act of deferring — whether from a task being processed, a scope item from a client meeting, or a new idea captured during a review.

```php
// app/Services/DeferralService.php

namespace App\Services;

use App\Models\{Task, DeferredItem, ClientMeeting, MeetingScopeItem};
use Carbon\Carbon;

class DeferralService
{
    /**
     * Defer a task with full context.
     * Called when a task is moved to 'deferred' status during processing.
     */
    public function deferTask(
        Task $task,
        string $reason,
        string $note = '',
        ?string $revisitDate = null,
        ?string $trigger = null,
        string $opportunityType = 'none',
        ?float $estimatedValue = null
    ): DeferredItem {

        // Update the task itself
        $task->update([
            'status'          => 'deferred',
            'deferral_reason' => $reason,
            'deferral_note'   => $note,
            'revisit_date'    => $revisitDate,
            'deferral_trigger' => $trigger,
            'has_opportunity'  => $opportunityType !== 'none',
        ]);

        // Create the deferred item record
        $item = DeferredItem::create([
            'user_id'          => $task->user_id,
            'task_id'          => $task->id,
            'project_id'       => $task->project_id,
            'title'            => $task->title,
            'description'      => $task->notes,
            'deferral_reason'  => $reason,
            'opportunity_type' => $opportunityType,
            'estimated_value'  => $estimatedValue,
            'status'           => $revisitDate ? 'scheduled' : 'someday',
            'deferred_on'      => today(),
            'revisit_date'     => $revisitDate,
            'revisit_trigger'  => $trigger,
            'client_name'      => $task->project?->client_name,
            'why_it_matters'   => $note,
        ]);

        // Auto-generate AI opportunity analysis if commercial value detected
        if ($opportunityType !== 'none' && $opportunityType !== 'personal-goal') {
            $this->queueOpportunityAnalysis($item);
        }

        return $item;
    }

    /**
     * Capture a deferred item directly from a scope item in a client meeting.
     * Out-of-scope and deferred items from meeting transcripts flow here.
     */
    public function captureFromScopeItem(
        MeetingScopeItem $scopeItem,
        string $opportunityType = 'phase-2',
        ?string $revisitDate = null
    ): DeferredItem {

        $project = $scopeItem->meeting->project;

        return DeferredItem::create([
            'user_id'          => auth()->id(),
            'meeting_id'       => $scopeItem->meeting_id,
            'scope_item_id'    => $scopeItem->id,
            'project_id'       => $project?->id,
            'title'            => $scopeItem->description,
            'client_context'   => $scopeItem->client_quote,
            'client_name'      => $project?->client_name,
            'client_quote'     => $scopeItem->client_quote,
            'deferral_reason'  => $this->mapScopeTypeToDeferralReason($scopeItem->type),
            'opportunity_type' => $opportunityType,
            'status'           => $revisitDate ? 'scheduled' : 'someday',
            'deferred_on'      => today(),
            'revisit_date'     => $revisitDate,
        ]);
    }

    /**
     * Capture a freeform idea for the Someday/Maybe list.
     * Quick capture during a brainstorm, meeting, or daily review.
     */
    public function captureIdea(
        string $title,
        string $description = '',
        string $opportunityType = 'none',
        ?int $projectId = null,
        ?string $clientName = null
    ): DeferredItem {

        return DeferredItem::create([
            'user_id'          => auth()->id(),
            'project_id'       => $projectId,
            'title'            => $title,
            'description'      => $description,
            'client_name'      => $clientName,
            'opportunity_type' => $opportunityType,
            'deferral_reason'  => 'priority',
            'status'           => 'someday',
            'deferred_on'      => today(),
        ]);
    }

    /**
     * Capture a personal goal with resource requirements.
     * Personal goals are deferred items where the client is yourself.
     */
    public function capturePersonalGoal(
        string $title,
        string $description = '',
        string $opportunityType = 'personal-goal',
        ?array $resourceRequirements = null,
        ?string $revisitDate = null,
        ?string $revisitTrigger = null
    ): DeferredItem {

        $deferralReason = $this->detectDeferralReason($resourceRequirements ?? []);

        return DeferredItem::create([
            'user_id'                => auth()->id(),
            'title'                  => $title,
            'description'            => $description,
            'client_type'            => 'self',
            'opportunity_type'       => $opportunityType,
            'deferral_reason'        => $deferralReason,
            'resource_requirements'  => $resourceRequirements,
            'resource_check_done'    => false,
            'status'                 => $revisitDate ? 'scheduled' : 'someday',
            'deferred_on'            => today(),
            'revisit_date'           => $revisitDate,
            'revisit_trigger'        => $revisitTrigger,
        ]);
    }

    /**
     * Process a weekly review of the Someday/Maybe list.
     * Returns items due for review, grouped by opportunity type.
     */
    public function getWeeklyReviewItems(): array
    {
        $items = DeferredItem::dueForReview()
            ->orderBy('estimated_value', 'desc')
            ->orderBy('deferred_on')
            ->get();

        return [
            'overdue'    => $items->filter(fn ($i) => $i->isOverdue()),
            'scheduled'  => $items->where('status', 'scheduled')
                                  ->where('revisit_date', '<=', today()->addDays(7)),
            'someday'    => $items->where('status', 'someday')
                                  ->where('opportunity_type', '!=', 'none')
                                  ->take(10), // Surface top 10 in weekly review
            'commercial' => $items->hasCommercialValue(),
        ];
    }

    /**
     * Submit a deferral review — log outcome and update item.
     */
    public function submitReview(
        DeferredItem $item,
        string $outcome,
        string $notes = '',
        ?string $nextRevisitDate = null
    ): void {

        \App\Models\DeferralReview::create([
            'user_id'           => $item->user_id,
            'deferred_item_id'  => $item->id,
            'reviewed_on'       => today(),
            'outcome'           => $outcome,
            'next_revisit_date' => $nextRevisitDate,
            'review_notes'      => $notes,
        ]);

        $item->increment('review_count');
        $item->update(['last_reviewed_at' => now()]);

        match($outcome) {
            'keep-someday' => $item->update(['status' => 'someday', 'revisit_date' => null]),
            'reschedule'   => $item->update(['status' => 'scheduled', 'revisit_date' => $nextRevisitDate]),
            'promote'      => $item->promote(),
            'propose'      => $item->update(['status' => 'promoted']) && $this->flagForProposal($item),
            'archive'      => $item->update(['status' => 'archived']),
            default        => null,
        };
    }

    // ── Private Helpers ───────────────────────────────────────────────

    private function mapScopeTypeToDeferralReason(string $scopeType): string
    {
        return match($scopeType) {
            'out-of-scope' => 'scope-control',
            'deferred'     => 'timeline',
            'assumption'   => 'awaiting-decision',
            'risk'         => 'priority',
            default        => 'priority',
        };
    }

    private function detectDeferralReason(array $resourceRequirements): string
    {
        // Analyze resource requirements to suggest the primary deferral reason
        if (isset($resourceRequirements['money']) && $resourceRequirements['money'] > 0) {
            return 'budget';
        }

        if (isset($resourceRequirements['time']) && $resourceRequirements['time'] > 20) {
            return 'timeline';
        }

        if (isset($resourceRequirements['capability'])) {
            return 'client-not-ready'; // Prerequisite skill needed
        }

        if (isset($resourceRequirements['technology'])) {
            return 'technology';
        }

        if (isset($resourceRequirements['energy']) && in_array($resourceRequirements['energy'], ['high', 'maximum'])) {
            return 'priority';
        }

        if (isset($resourceRequirements['readiness']) && $resourceRequirements['readiness'] === false) {
            return 'awaiting-decision';
        }

        if (isset($resourceRequirements['dependency'])) {
            return 'scope-control'; // Protecting focus
        }

        return 'priority';
    }

    private function flagForProposal(DeferredItem $item): void
    {
        if (!$item->opportunity) {
            $item->promote();
        }

        $item->opportunity->update(['stage' => 'proposing']);
    }

    private function queueOpportunityAnalysis(DeferredItem $item): void
    {
        // Dispatched as a queued job to avoid slowing down the UI
        \App\Jobs\AnalyzeDeferredOpportunity::dispatch($item);
    }
}
```

---

## 7. Opportunity Pipeline Service

```php
// app/Services/OpportunityPipelineService.php

namespace App\Services;

use App\Models\OpportunityPipeline;
use App\Models\DeferredItem;

class OpportunityPipelineService
{
    /**
     * Get the full pipeline summary — total value, weighted value,
     * items by stage, and next actions due this week.
     */
    public function getSummary(): array
    {
        $opportunities = OpportunityPipeline::whereNotIn('stage', ['closed-won', 'closed-lost'])
            ->with('deferredItem')
            ->get();

        $totalValue    = $opportunities->sum('estimated_value');
        $weightedValue = $opportunities->sum(fn ($o) => $o->weightedValue());

        $byStage = $opportunities->groupBy('stage')
            ->map(fn ($group) => [
                'count'    => $group->count(),
                'value'    => $group->sum('estimated_value'),
                'weighted' => $group->sum(fn ($o) => $o->weightedValue()),
            ]);

        $actionsThisWeek = $opportunities
            ->whereNotNull('next_action_date')
            ->where('next_action_date', '<=', today()->addDays(7))
            ->sortBy('next_action_date');

        return compact('totalValue', 'weightedValue', 'byStage', 'actionsThisWeek');
    }

    /**
     * Advance an opportunity to the next pipeline stage.
     */
    public function advanceStage(OpportunityPipeline $opportunity): void
    {
        $stages = [
            'identified', 'qualifying', 'nurturing',
            'proposing', 'negotiating', 'closed-won',
        ];

        $currentIndex = array_search($opportunity->stage, $stages);
        if ($currentIndex !== false && isset($stages[$currentIndex + 1])) {
            $opportunity->update(['stage' => $stages[$currentIndex + 1]]);
        }
    }

    /**
     * Close an opportunity as won.
     * Optionally creates a new Project from the opportunity details.
     */
    public function closeWon(
        OpportunityPipeline $opportunity,
        float $actualValue,
        bool $createProject = true
    ): ?int {

        $opportunity->update([
            'stage'             => 'closed-won',
            'actual_value'      => $actualValue,
            'actual_close_date' => today(),
            'probability_percent' => 100,
        ]);

        $opportunity->deferredItem->update(['status' => 'won']);

        if ($createProject) {
            $project = \App\Models\Project::create([
                'user_id'       => $opportunity->user_id,
                'life_area_id'  => $opportunity->deferredItem?->task?->life_area_id
                                   ?? \App\Models\LifeArea::where('name', 'Business')->value('id'),
                'name'          => $opportunity->title,
                'description'   => $opportunity->description,
                'client_name'   => $opportunity->client_name,
                'status'        => 'active',
            ]);

            $opportunity->update(['project_id' => $project->id]);

            return $project->id;
        }

        return null;
    }

    /**
     * Get all items in the Someday/Maybe list that have commercial value
     * and have not been reviewed recently — for the weekly review prompt.
     */
    public function getStaleHighValueItems(): \Illuminate\Database\Eloquent\Collection
    {
        return DeferredItem::hasCommercialValue()
            ->where(fn ($q) =>
                $q->whereNull('last_reviewed_at')
                  ->orWhere('last_reviewed_at', '<', now()->subDays(30))
            )
            ->orderByDesc('estimated_value')
            ->limit(10)
            ->get();
    }

    /**
     * Calculate the total pipeline value across all open opportunities.
     */
    public function totalWeightedPipeline(): float
    {
        return OpportunityPipeline::where('user_id', auth()->id())
            ->whereNotIn('stage', ['closed-won', 'closed-lost'])
            ->get()
            ->sum(fn ($o) => $o->weightedValue());
    }
}
```

---

## 8. Filament Resources

### DeferredItemResource

```php
protected static ?string $model = DeferredItem::class;
protected static ?string $navigationIcon = 'heroicon-o-archive-box';
protected static ?string $navigationGroup = 'Goals & Projects';
protected static ?string $navigationLabel = 'Someday / Maybe';
protected static ?int $navigationSort = 7;

public static function form(Form $form): Form
{
    return $form->schema([
        Section::make('Deferred Item')->schema([
            TextInput::make('title')->required()->columnSpanFull(),

            Grid::make(2)->schema([
                Select::make('project_id')
                    ->label('Project')
                    ->relationship('project', 'name')
                    ->searchable()
                    ->nullable(),

                TextInput::make('client_name')->nullable(),
            ]),

            Textarea::make('description')->rows(2)->columnSpanFull(),

            Textarea::make('client_context')
                ->label('Client Context')
                ->helperText('What did the client say? What was the situation when this was deferred?')
                ->rows(3)
                ->columnSpanFull(),

            TextInput::make('client_quote')
                ->label('Client Quote')
                ->helperText('Their exact words, if you have them')
                ->nullable()
                ->columnSpanFull(),

            Textarea::make('why_it_matters')
                ->label('Why It Still Matters')
                ->rows(2)
                ->placeholder('Why is this worth keeping? What value does it represent?')
                ->columnSpanFull(),
        ]),

        Section::make('Classification & Timing')->schema([
            Grid::make(3)->schema([
                Select::make('client_type')
                    ->label('Client Type')
                    ->options([
                        'external' => 'External Client',
                        'self'     => 'Personal Goal',
                    ])
                    ->default('external')
                    ->required()
                    ->live(),

                Select::make('deferral_reason')
                    ->label('Why Deferred')
                    ->options([
                        'budget'            => '💰 Budget',
                        'timeline'          => '📅 Timeline',
                        'priority'          => '📌 Priority',
                        'client-not-ready'  => '⏳ Client Not Ready',
                        'scope-control'     => '🛡️ Scope Control',
                        'awaiting-decision' => '🤔 Awaiting Decision',
                        'technology'        => '⚙️ Technology',
                        'personal'          => '🌱 Personal',
                    ])
                    ->required(),

                Select::make('opportunity_type')
                    ->label('Opportunity Type')
                    ->options([
                        'phase-2'        => '🚀 Phase 2',
                        'upsell'         => '📈 Upsell',
                        'upgrade'        => '⬆️ Upgrade',
                        'new-project'    => '🆕 New Project',
                        'retainer'       => '🔄 Retainer',
                        'product-feature'=> '🏗️ Product Feature',
                        'personal-goal'  => '🌟 Personal Goal',
                        'personal-development' => '🎓 Personal Development',
                        'none'           => '—  None',
                    ])
                    ->required()
                    ->live(),
            ]),

            Grid::make(3)->schema([
                TextInput::make('estimated_value')
                    ->label('Estimated Value ($)')
                    ->numeric()
                    ->prefix('$')
                    ->visible(fn ($get) =>
                        !in_array($get('opportunity_type'), ['none', 'personal-goal'])
                    ),

                DatePicker::make('revisit_date')
                    ->label('Revisit Date')
                    ->nullable(),

                Select::make('status')
                    ->options([
                        'someday'   => '🌌 Someday / Maybe',
                        'scheduled' => '📅 Scheduled',
                        'in-review' => '🔍 In Review',
                        'promoted'  => '⬆️ In Pipeline',
                        'proposed'  => '📄 Proposed',
                        'won'       => '🏆 Won',
                        'lost'      => '❌ Lost',
                        'archived'  => '📦 Archived',
                    ])
                    ->default('someday'),
            ]),

            TextInput::make('revisit_trigger')
                ->label('Revisit Trigger')
                ->helperText('What event should bring this back to the surface?')
                ->placeholder('e.g. After current project launches, When Q1 budget opens')
                ->nullable()
                ->columnSpanFull(),

            TextInput::make('value_notes')
                ->label('Value Notes')
                ->placeholder('How did you arrive at this estimate?')
                ->nullable()
                ->columnSpanFull(),
        ]),

        Section::make('Personal Resources Required')
            ->visible(fn ($get) => $get('client_type') === 'self')
            ->schema([
                Grid::make(2)->schema([
                    TextInput::make('resource_requirements.time')
                        ->label('Time Required (hours)')
                        ->numeric()
                        ->nullable(),

                    TextInput::make('resource_requirements.money')
                        ->label('Money Required ($)')
                        ->numeric()
                        ->nullable(),

                    TextInput::make('resource_requirements.capability')
                        ->label('Skill/Capability Needed')
                        ->placeholder('e.g. "JavaScript", "Video editing"')
                        ->nullable(),

                    TextInput::make('resource_requirements.technology')
                        ->label('Technology/Tool Needed')
                        ->placeholder('e.g. "Better camera", "Cloud storage"')
                        ->nullable(),

                    Select::make('resource_requirements.energy')
                        ->label('Energy Level Required')
                        ->options([
                            'low'       => 'Low',
                            'medium'    => 'Medium',
                            'high'      => 'High',
                            'maximum'   => 'Maximum',
                        ])
                        ->nullable(),

                    TextInput::make('resource_requirements.dependency')
                        ->label('Dependent On')
                        ->placeholder('e.g. "Complete manuscript first"')
                        ->nullable(),
                ]),

                Checkbox::make('resource_check_done')
                    ->label('Resource constraints have been verified'),
            ]),

        Section::make('AI Opportunity Analysis')->schema([
            Placeholder::make('ai_opportunity_analysis')
                ->label('')
                ->content(fn ($record) =>
                    $record?->ai_opportunity_analysis
                    ?? 'Save this item to generate an AI opportunity analysis.'
                )
                ->columnSpanFull(),
        ]),
    ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('title')
                ->searchable()
                ->weight('bold')
                ->wrap()
                ->description(fn ($record) => $record->client_name),

            TextColumn::make('opportunity_type')
                ->label('Type')
                ->badge()
                ->color(fn (string $state): string => match($state) {
                    'phase-2'         => 'success',
                    'upsell'          => 'warning',
                    'upgrade'         => 'info',
                    'new-project'     => 'danger',
                    'retainer'        => 'success',
                    'product-feature' => 'info',
                    'personal-goal'   => 'gray',
                    default           => 'gray',
                }),

            TextColumn::make('deferral_reason')
                ->label('Reason')
                ->badge()
                ->color('gray'),

            TextColumn::make('estimated_value')
                ->label('Value')
                ->money('USD')
                ->sortable()
                ->color('success'),

            TextColumn::make('status')
                ->badge()
                ->color(fn (string $state): string => match($state) {
                    'someday'   => 'gray',
                    'scheduled' => 'info',
                    'in-review' => 'warning',
                    'promoted'  => 'success',
                    'proposed'  => 'warning',
                    'won'       => 'success',
                    'lost'      => 'danger',
                    'archived'  => 'gray',
                    default     => 'gray',
                }),

            TextColumn::make('revisit_date')
                ->label('Revisit')
                ->date('M j, Y')
                ->sortable()
                ->color(fn ($record) =>
                    $record->isOverdue() ? 'danger' : 'gray'
                ),

            TextColumn::make('review_count')
                ->label('Reviews')
                ->alignCenter()
                ->color('gray'),
        ])
        ->defaultSort('estimated_value', 'desc')
        ->filters([
            SelectFilter::make('opportunity_type')
                ->label('Opportunity Type')
                ->options([
                    'phase-2'         => 'Phase 2',
                    'upsell'          => 'Upsell',
                    'upgrade'         => 'Upgrade',
                    'new-project'     => 'New Project',
                    'retainer'        => 'Retainer',
                    'product-feature' => 'Product Feature',
                ]),

            SelectFilter::make('status')
                ->options([
                    'someday'   => 'Someday / Maybe',
                    'scheduled' => 'Scheduled',
                    'promoted'  => 'In Pipeline',
                ]),

            Filter::make('overdue')
                ->label('Overdue for Review')
                ->query(fn ($query) =>
                    $query->where('revisit_date', '<=', today())
                          ->whereIn('status', ['someday', 'scheduled'])
                ),

            Filter::make('high_value')
                ->label('High Value (> $5k)')
                ->query(fn ($query) =>
                    $query->where('estimated_value', '>=', 5000)
                ),
        ], layout: FiltersLayout::AboveContent)
        ->actions([
            \Filament\Tables\Actions\Action::make('review')
                ->label('Review')
                ->icon('heroicon-o-eye')
                ->color('warning')
                ->visible(fn ($record) => $record->isOverdue() || $record->status === 'someday')
                ->form([
                    Select::make('outcome')
                        ->options([
                            'keep-someday' => 'Keep in Someday / Maybe',
                            'reschedule'   => 'Reschedule',
                            'promote'      => 'Move to Pipeline',
                            'propose'      => 'Ready to Propose',
                            'archive'      => 'Archive',
                        ])
                        ->required()
                        ->live(),
                    DatePicker::make('next_revisit_date')
                        ->visible(fn ($get) => $get('outcome') === 'reschedule'),
                    Textarea::make('notes')->rows(2),
                ])
                ->action(fn ($record, array $data) =>
                    app(DeferralService::class)->submitReview(
                        $record,
                        $data['outcome'],
                        $data['notes'] ?? '',
                        $data['next_revisit_date'] ?? null
                    )
                ),

            \Filament\Tables\Actions\Action::make('promote')
                ->label('Add to Pipeline')
                ->icon('heroicon-o-arrow-trending-up')
                ->color('success')
                ->visible(fn ($record) =>
                    $record->opportunity_type !== 'none'
                    && !in_array($record->status, ['promoted', 'proposed', 'won', 'archived'])
                )
                ->action(fn ($record) => $record->promote()),

            EditAction::make(),
        ])
        ->bulkActions([
            BulkActionGroup::make([
                \Filament\Tables\Actions\BulkAction::make('archive')
                    ->label('Archive Selected')
                    ->icon('heroicon-o-archive-box')
                    ->action(fn ($records) =>
                        $records->each->update(['status' => 'archived'])
                    ),
            ]),
        ]);
}
```

---

### OpportunityPipelineResource

```php
protected static ?string $model = OpportunityPipeline::class;
protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';
protected static ?string $navigationGroup = 'Goals & Projects';
protected static ?string $navigationLabel = 'Opportunity Pipeline';
protected static ?int $navigationSort = 8;

public static function form(Form $form): Form
{
    return $form->schema([
        Section::make('Opportunity')->schema([
            TextInput::make('title')->required()->columnSpanFull(),

            Grid::make(2)->schema([
                TextInput::make('client_name')->required(),
                TextInput::make('client_email')->email()->nullable(),
            ]),

            Textarea::make('description')->rows(3)->columnSpanFull(),
        ]),

        Section::make('Pipeline Status')->schema([
            Grid::make(2)->schema([
                Select::make('stage')
                    ->options([
                        'identified'  => '🔍 Identified',
                        'qualifying'  => '📋 Qualifying',
                        'nurturing'   => '🌱 Nurturing',
                        'proposing'   => '📄 Proposing',
                        'negotiating' => '🤝 Negotiating',
                        'closed-won'  => '🏆 Closed Won',
                        'closed-lost' => '❌ Closed Lost',
                    ])
                    ->required()
                    ->live(),

                TextInput::make('probability_percent')
                    ->label('Probability %')
                    ->numeric()
                    ->suffix('%')
                    ->default(20),
            ]),

            Grid::make(3)->schema([
                TextInput::make('estimated_value')
                    ->label('Estimated Value ($)')
                    ->numeric()
                    ->prefix('$'),

                DatePicker::make('expected_close_date')
                    ->label('Expected Close'),

                TextInput::make('actual_value')
                    ->label('Actual Value ($)')
                    ->numeric()
                    ->prefix('$')
                    ->visible(fn ($get) => $get('stage') === 'closed-won'),
            ]),
        ]),

        Section::make('Next Action')->schema([
            Grid::make(2)->schema([
                DatePicker::make('next_action_date')->label('Due Date'),
                Textarea::make('next_action')->label('Action')->rows(2),
            ]),
        ]),

        Textarea::make('notes')->rows(3)->columnSpanFull(),

        Textarea::make('lost_reason')
            ->label('Lost Reason')
            ->rows(2)
            ->visible(fn ($get) => $get('stage') === 'closed-lost')
            ->columnSpanFull(),
    ]);
}

public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('title')->searchable()->weight('bold')->wrap(),
            TextColumn::make('client_name')->label('Client')->searchable(),

            TextColumn::make('stage')
                ->badge()
                ->color(fn (string $state): string => match($state) {
                    'identified'  => 'gray',
                    'qualifying'  => 'info',
                    'nurturing'   => 'warning',
                    'proposing'   => 'warning',
                    'negotiating' => 'danger',
                    'closed-won'  => 'success',
                    'closed-lost' => 'danger',
                    default       => 'gray',
                }),

            TextColumn::make('estimated_value')
                ->label('Value')
                ->money('USD')
                ->sortable(),

            TextColumn::make('probability_percent')
                ->label('Prob.')
                ->suffix('%')
                ->alignCenter()
                ->color('gray'),

            TextColumn::make('next_action_date')
                ->label('Next Action')
                ->date('M j')
                ->sortable()
                ->color(fn ($record) =>
                    $record->next_action_date?->isPast() ? 'danger' : 'gray'
                ),
        ])
        ->defaultSort('next_action_date')
        ->filters([
            SelectFilter::make('stage')
                ->options([
                    'identified'  => 'Identified',
                    'qualifying'  => 'Qualifying',
                    'nurturing'   => 'Nurturing',
                    'proposing'   => 'Proposing',
                    'negotiating' => 'Negotiating',
                ]),
        ])
        ->actions([
            \Filament\Tables\Actions\Action::make('advance')
                ->label('Advance Stage')
                ->icon('heroicon-o-arrow-right')
                ->color('success')
                ->visible(fn ($record) =>
                    !in_array($record->stage, ['closed-won', 'closed-lost'])
                )
                ->action(fn ($record) =>
                    app(OpportunityPipelineService::class)->advanceStage($record)
                ),
            EditAction::make(),
        ]);
}
```

---

## 9. AI Integration Points

### 1. Opportunity Analysis

When a deferred item is saved with a commercial opportunity type, AI analyzes it and writes a structured opportunity brief. For personal goals, it uses a personal readiness assessment prompt instead.

```php
// In AiService.php

public function analyzeOpportunity(DeferredItem $item): string
{
    if ($item->client_type === 'self') {
        return $this->analyzePersonalGoal($item);
    }

    $prompt = <<<PROMPT
You are a business development advisor. Analyze this deferred client opportunity
and write a brief opportunity profile.

ITEM: "{$item->title}"
CLIENT: {$item->client_name}
DEFERRED REASON: {$item->deferral_reason}
OPPORTUNITY TYPE: {$item->opportunity_type}
CLIENT CONTEXT: {$item->client_context}
CLIENT QUOTE: {$item->client_quote}
ESTIMATED VALUE: ${$item->estimated_value}
PROJECT CONTEXT: {$item->project?->name}

Write a 3–4 paragraph opportunity brief covering:
1. Why this opportunity exists and what the client's underlying need is
2. What the right timing and trigger for this conversation looks like
3. How to re-open the conversation naturally — without being pushy
4. What a compelling proposal would focus on

Be specific and actionable. Speak to someone who knows the client well.
PROMPT;

    $response = $this->chat($prompt, 'freeform', [
        'deferred_item_id' => $item->id,
        'opportunity_type' => $item->opportunity_type,
    ]);

    $item->update(['ai_opportunity_analysis' => $response]);

    return $response;
}

private function analyzePersonalGoal(DeferredItem $item): string
{
    $resourcesJson = json_encode($item->resource_requirements ?? [], JSON_PRETTY_PRINT);

    $prompt = <<<PROMPT
You are a personal development advisor. Analyze this personal goal and write a
personal readiness assessment covering when and how to pursue it.

GOAL: "{$item->title}"
DESCRIPTION: {$item->description}
WHY IT MATTERS: {$item->why_it_matters}
DEFERRED REASON: {$item->deferral_reason}
OPPORTUNITY TYPE: {$item->opportunity_type}

RESOURCE CONSTRAINTS:
{$resourcesJson}

Write a 3–4 paragraph assessment covering:
1. What resources are blocking this goal and when they might become available
2. What prerequisite skills or capabilities need to develop first
3. What milestones or life events would signal it's the right time
4. How to prepare or maintain momentum while waiting for the right moment

Be encouraging and practical. Help this person see the path forward.
PROMPT;

    $response = $this->chat($prompt, 'freeform', [
        'deferred_item_id' => $item->id,
        'opportunity_type' => $item->opportunity_type,
        'client_type'      => 'self',
    ]);

    $item->update(['ai_opportunity_analysis' => $response]);

    return $response;
}
```

---

### 2. Weekly Someday/Maybe Scan

During the weekly review, AI scans the full Someday/Maybe list and surfaces the top 3 items most worth revisiting this week, with reasoning:

```php
public function scanSomedayMaybe(array $items): string
{
    $itemList = collect($items)
        ->map(fn ($i) =>
            "- {$i->title} | Client: {$i->client_name} | Value: \${$i->estimated_value} | Deferred: {$i->deferred_on->diffForHumans()}"
        )
        ->join("\n");

    $prompt = <<<PROMPT
You are reviewing a Someday/Maybe list for a freelance creative professional.

CURRENT DATE: {$today = today()->format('M j, Y')}

SOMEDAY/MAYBE ITEMS:
{$itemList}

Review this list and identify the top 3 items most worth acting on THIS WEEK.
For each, explain:
- Why now is a good time to revisit it
- What the ideal next action is
- What to say to re-open the conversation

Be practical and direct. This person is busy — give them a reason to act.
PROMPT;

    return $this->chat($prompt, 'weekly');
}
```

---

### 3. Pattern Detection Across Deferrals

Once you have 10+ deferred items, AI can detect patterns — repeated asks from different clients that suggest a service you should productize:

```php
public function detectPatterns(): string
{
    $items = DeferredItem::hasCommercialValue()
        ->whereNotIn('status', ['archived', 'lost'])
        ->get(['title', 'client_name', 'opportunity_type', 'estimated_value']);

    $list = $items->map(fn ($i) =>
        "[{$i->opportunity_type}] {$i->title} — {$i->client_name}"
    )->join("\n");

    $prompt = <<<PROMPT
Analyze this list of deferred client requests and identify patterns.

DEFERRED ITEMS:
{$list}

Look for:
1. Repeated asks across multiple clients suggesting a productizable service
2. Common themes that suggest an unmet market need
3. High-value clusters worth building a dedicated offer around
4. Seasonal or timing patterns

Write a strategic analysis with specific recommendations for services or
packages this person could build to capture recurring revenue from these patterns.
PROMPT;

    return $this->chat($prompt, 'freeform');
}
```

---

## 10. The Review Cadence

Deferred items need a structured review cadence or they rot. Solas Rún enforces this through the weekly and monthly rhythms.

### Weekly Review Integration

Every Friday/Sunday review automatically includes a **Someday/Maybe Scan**:

- Items with a `revisit_date` in the past 7 days are flagged as overdue
- Items in the pipeline with a `next_action_date` this week are surfaced
- Top 3 high-value someday items are presented for a quick yes/no/reschedule decision
- AI runs the Someday/Maybe Scan and presents its top 3 recommendations

### Monthly Opportunity Review *(30 min)*

Once a month, a dedicated Opportunity Review session:

- Full pipeline review — advance, stall, or close each opportunity
- Pattern detection AI analysis — are new services worth building?
- Someday items older than 90 days — archive or recommit
- Revenue forecast — weighted pipeline value vs. actual bookings

### Automatic Resurface Rules

The system automatically flags items for review when:

| Condition | Action |
|-----------|--------|
| `revisit_date` reaches today | Status → `in-review`, notification sent |
| Someday item not reviewed in 30 days | Surfaced in weekly review |
| Someday item not reviewed in 90 days | Prompted to archive or recommit |
| Related project is marked complete | All linked deferred items resurface |
| New meeting added to same client/project | Linked deferred items highlighted |
| Personal goal's `resource_requirements` estimate date is reached | Flagged for resource readiness review |
| Related capability goal is marked achieved | Linked personal goals resurface |
| Finance goal reaches a milestone | Budget-deferred items resurface |
| Energy/health metrics improve over 4+ weeks | Readiness-deferred items surface in weekly review |

---

## 11. Dashboard Widget — Opportunity Pipeline

A compact pipeline summary widget for the dashboard showing total weighted value and items needing action this week.

```php
// app/Filament/Widgets/OpportunityPipelineWidget.php

namespace App\Filament\Widgets;

use App\Services\OpportunityPipelineService;
use App\Models\DeferredItem;

class OpportunityPipelineWidget extends BaseWidget
{
    protected static ?int $sort = 8;
    protected int | string | array $columnSpan = 1;
    protected static string $view = 'filament.widgets.opportunity-pipeline-widget';

    public function getViewData(): array
    {
        $service      = app(OpportunityPipelineService::class);
        $summary      = $service->getSummary();
        $overdueItems = DeferredItem::dueForReview()->count();
        $staleHighValue = $service->getStaleHighValueItems()->count();

        return [
            'weightedValue'    => $summary['weightedValue'],
            'totalValue'       => $summary['totalValue'],
            'byStage'          => $summary['byStage'],
            'actionsThisWeek'  => $summary['actionsThisWeek'],
            'overdueItems'     => $overdueItems,
            'staleHighValue'   => $staleHighValue,
        ];
    }
}
```

```blade
{{-- resources/views/filament/widgets/opportunity-pipeline-widget.blade.php --}}

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">📈 Opportunity Pipeline</x-slot>
        <x-slot name="headerEnd">
            <a href="{{ route('filament.admin.resources.opportunity-pipeline.index') }}"
               class="text-xs text-primary-500 hover:underline">View All →</a>
        </x-slot>

        {{-- Weighted pipeline value --}}
        <div class="text-center py-3 mb-4 bg-success-50 dark:bg-success-950 rounded-lg">
            <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Weighted Pipeline</p>
            <p class="text-2xl font-bold text-success-600">
                ${{ number_format($weightedValue, 0) }}
            </p>
            <p class="text-xs text-gray-400">
                of ${{ number_format($totalValue, 0) }} total identified
            </p>
        </div>

        {{-- Actions due this week --}}
        @if($actionsThisWeek->isNotEmpty())
            <div class="mb-4">
                <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-2">
                    Actions Due This Week
                </p>
                @foreach($actionsThisWeek->take(3) as $opp)
                    <div class="flex items-start gap-2 py-1.5 border-b border-gray-100 dark:border-gray-800 last:border-0">
                        <span class="text-warning-500 flex-shrink-0 mt-0.5">→</span>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-700 dark:text-gray-300 truncate">
                                {{ $opp->client_name }}
                            </p>
                            <p class="text-xs text-gray-400 truncate">{{ $opp->next_action }}</p>
                        </div>
                        <span class="text-xs text-gray-400 flex-shrink-0 ml-auto">
                            {{ $opp->next_action_date->format('M j') }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Alerts --}}
        @if($overdueItems > 0)
            <a href="{{ route('filament.admin.resources.deferred-items.index', ['tableFilters[overdue][isActive]' => true]) }}"
               class="flex items-center gap-2 p-2 rounded-lg bg-warning-50 dark:bg-warning-950 mb-2 hover:bg-warning-100 transition-colors">
                <span class="text-warning-500">⚠️</span>
                <span class="text-sm text-warning-700 dark:text-warning-400">
                    {{ $overdueItems }} items overdue for review
                </span>
            </a>
        @endif

        @if($staleHighValue > 0)
            <a href="{{ route('filament.admin.resources.deferred-items.index') }}"
               class="flex items-center gap-2 p-2 rounded-lg bg-info-50 dark:bg-info-950 hover:bg-info-100 transition-colors">
                <span class="text-info-500">💡</span>
                <span class="text-sm text-info-700 dark:text-info-400">
                    {{ $staleHighValue }} high-value opportunities need attention
                </span>
            </a>
        @endif

    </x-filament::section>
</x-filament-widgets::widget>
```

---

*Solas Rún • Version 1.1 • GTD Deferral & Opportunity Pipeline*
