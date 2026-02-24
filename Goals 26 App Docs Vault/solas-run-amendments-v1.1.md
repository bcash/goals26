# SOLAS RÚN
### *Document Amendments & Change Log*
**v1.1 — Meeting Intelligence, Self-as-Client, Done Tracking, Resource Readiness**

---

## Overview

This document records all changes made to existing Solas Rún documentation as a result of the following decisions:

1. **Meeting Transcription API** — meetings are now a first-class data source via webhook
2. **Agenda Builder** — structured preparation for future meetings
3. **Done Item Tracking** — completed work tracked as richly as deferred work
4. **Self-as-Client Model** — you are your own most important client; internal goals follow the same discipline as client projects
5. **Personal Resource Readiness** — time, money, technology, and capability are tracked as deferral constraints, just like client budget
6. **Wish-List / Future-Vision Items** — ambitious ideas beyond current resources are captured and valued, not discarded
7. **Resource Signals** — mentions of constraints extracted from transcripts and stored
8. **Goal Brainstorming** — AI-facilitated internal planning sessions

All new content lives in:
- `solas-run-meeting-intelligence.md` *(new)*

This document specifies exactly what changes in each existing doc.

---

## Table of Contents

1. [Blueprint — solas-run-blueprint.md](#1-blueprint)
2. [Laravel Setup — solas-run-laravel-setup.md](#2-laravel-setup)
3. [Multi-Tenancy — solas-run-multitenancy.md](#3-multi-tenancy)
4. [Filament Resources — solas-run-filament-resources.md](#4-filament-resources)
5. [Dashboard Widgets — solas-run-dashboard-widgets.md](#5-dashboard-widgets)
6. [Task Decomposition — solas-run-task-decomposition.md](#6-task-decomposition)
7. [Deferral Pipeline — solas-run-deferral-pipeline.md](#7-deferral-pipeline)

---

## 1. Blueprint

**File:** `solas-run-blueprint.md`

### Section I — Vision & Philosophy

**Add after "Core Philosophy":**

> The client relationship in Solas Rún is not limited to external paying customers. **You are your own most important client.** Your creative projects, life goals, health ambitions, and personal development follow the same discipline as any professional engagement: they have a scope, a budget of resources, deliverables, a quality standard, and things that are in-scope, deferred, or not worth pursuing. When you sit down to plan your week, that is a meeting with yourself. Treat it with the same rigor.

---

### Section I — The Six Life Areas

**Update the table description for each area to include the self-as-client framing:**

| Area | Updated Scope |
|------|--------------|
| 🎨 Creative | Writing (sci-fi, lyrics, family history), music composition, TV production. You are the client, producer, and creative director. |
| 💼 Business | Client work, webmaster team, contracts, revenue, growth. External clients and internal team goals. |
| 💚 Health | Physical wellness, mental health, energy, sleep, nutrition, movement. Your body is a project with a budget, a roadmap, and a quality standard. |
| 👨‍👩‍👧 Family | Relationships, presence, shared experiences, legacy. These have timelines, deliverables, and things that can be deferred too long. |
| 📚 Growth | Learning, skills, reading, courses, curiosity, spiritual development. Resources needed: time, money, energy, and prerequisite capabilities. |
| 💰 Finance | Income, expenses, savings, investments, financial goals. The resource foundation that enables or constrains every other area. |

---

### Section II — The Daily Rhythm

**Add to Evening Session:**
- Review any done items logged from today's work — note outcomes and impact
- Flag any tasks completed today that produced notable results worth capturing

**Add to Weekly Review Session:**
- Review Someday/Maybe list — are any personal goals now resourced and ready to activate?
- Check opportunity pipeline — any deferred client or personal items with next actions due?

---

### Section III — Data Model

**Add to "Core Entities" table:**

| Entity | Purpose |
|--------|---------|
| `meeting_done_items` | Completed work confirmed in meetings — outcomes, client reactions, value delivered |
| `meeting_resource_signals` | Resource constraints mentioned in meetings — budget, time, capability, technology |
| `meeting_agendas` | Structured agendas for upcoming meetings |
| `agenda_items` | Individual topics, follow-ups, and deferred reviews on an agenda |

**Update `client_meetings` description:**
> Now includes `client_type` (external or self), transcription API fields, and links to done items, resource signals, and agendas. Supports automatic ingestion from Fireflies, AssemblyAI, Rev.ai, Whisper, and manual upload.

---

### Section IV — Filament UI Structure

**Update navigation group table:**

| Group | Updated Resources & Pages |
|-------|--------------------------|
| 🎯 Goals & Projects | Goals, Milestones, Projects, Tasks, Task Tree, Client Meetings, Meeting Agendas, Someday/Maybe, Opportunity Pipeline, Budgets |

**Add to Key Filament Pages — new pages:**

- **Meeting Agendas** — build agendas before meetings, auto-populate with open tasks and deferred items
- **Meeting Intelligence View** — post-meeting view showing done items, scope items, action items, and deferred items extracted from transcript
- **Goal Brainstorm** — AI-facilitated session for internal goals

---

### Section V — AI Features

**Update section heading:** "Eight AI Integration Points" *(was six)*

**Add:**

**7. Goal Brainstorming**
When you create a self-meeting of type `brainstorm` or `planning`, AI acts as a thinking partner — asking clarifying questions, helping define what success looks like, surfacing what resources are needed, and suggesting whether this is a 90-day, 1-year, or lifetime goal.

**8. Personal Resource Readiness Assessment**
When you consider activating a deferred personal goal, AI reviews your current load — active goals, habit count, recent energy and focus scores — and assesses whether now is the right time, what specifically was missing when you deferred it, and what the first three steps should be.

---

### Section VII — Build Phases

**Update Phase 2:**
- Add: Meeting transcription webhook endpoints
- Add: Basic agenda builder

**Update Phase 3:**
- Add: Full Meeting Intelligence extraction (done items, deferred items, resource signals)
- Add: Goal brainstorm AI session
- Add: Personal resource readiness assessment

**Update Phase 4:**
- Add: Done item portfolio view (case study and testimonial tracker)
- Add: Pattern detection across deferred items

---

## 2. Laravel Setup

**File:** `solas-run-laravel-setup.md`

### Section 5 — Install Supporting Packages

**Add after "Markdown Editor for Journal":**

#### Transcription API Clients

No additional Composer packages required. All transcription API integrations use Laravel's built-in `Http` facade.

Add API keys to `.env`:

```dotenv
# Transcription services — add whichever you use
FIREFLIES_API_KEY=
ASSEMBLYAI_API_KEY=
REV_API_KEY=
OPENAI_API_KEY=              # For Whisper self-transcription
```

Add to `config/services.php`:

```php
'assemblyai' => [
    'api_key' => env('ASSEMBLYAI_API_KEY'),
    'base_url' => 'https://api.assemblyai.com/v2',
],
'fireflies' => [
    'api_key' => env('FIREFLIES_API_KEY'),
],
```

---

### Section 6 — Directory & File Structure

**Add to `app/Services/`:**

```
app/
└── Services/
    ├── AiService.php
    ├── HabitStreakService.php
    ├── DailyPlanService.php
    ├── GoalProgressService.php
    ├── TaskTreeService.php
    ├── DecompositionInterviewService.php
    ├── QualityGateService.php
    ├── DeferralService.php
    ├── OpportunityPipelineService.php
    ├── BudgetService.php
    ├── MeetingIntelligenceService.php   ← NEW
    ├── AgendaService.php                ← NEW
    └── TranscriptionIngestionService.php ← NEW
```

**Add to `app/Http/Controllers/`:**

```
app/
└── Http/
    └── Controllers/
        └── TranscriptionWebhookController.php  ← NEW
```

**Add to `app/Jobs/`:**

```
app/
└── Jobs/
    ├── AnalyzeDeferredOpportunity.php
    ├── AnalyzeMeetingTranscript.php    ← NEW
    └── ProcessTranscriptionWebhook.php ← NEW
```

---

### Section 7 — Migrations

**Add new migrations (run in this order after existing migrations):**

```
2024_01_01_000013_add_transcription_fields_to_client_meetings_table.php
2024_01_01_000014_add_deferral_fields_to_tasks_table.php
2024_01_01_000015_create_task_quality_gates_table.php
2024_01_01_000016_create_deferred_items_table.php
2024_01_01_000017_create_opportunity_pipeline_table.php
2024_01_01_000018_create_deferral_reviews_table.php
2024_01_01_000019_create_meeting_done_items_table.php
2024_01_01_000020_create_meeting_resource_signals_table.php
2024_01_01_000021_create_meeting_agendas_table.php
2024_01_01_000022_create_agenda_items_table.php
2024_01_01_000023_create_project_budgets_table.php
2024_01_01_000024_create_time_entries_table.php
```

---

### Section 8 — Seeders

**No changes needed.** The `LifeAreaSeeder` remains unchanged. All new tables are populated by user activity.

---

### Section 9 — Models

**Add to model creation commands:**

```bash
php artisan make:model MeetingDoneItem
php artisan make:model MeetingResourceSignal
php artisan make:model MeetingAgenda
php artisan make:model AgendaItem
php artisan make:model DeferredItem
php artisan make:model OpportunityPipeline
php artisan make:model ProjectBudget
php artisan make:model TimeEntry
php artisan make:model TaskQualityGate
php artisan make:model DeferralReview
```

**Add to Artisan commands reference table:**

| Command | Purpose |
|---------|---------|
| `php artisan queue:work` | Process background jobs (transcript analysis, AI generation) |
| `php artisan make:job JobName` | Create a new queued job |

---

## 3. Multi-Tenancy

**File:** `solas-run-multitenancy.md`

### Section 1 — Tables That Need `user_id`

**Update table:**

| Table | Needs `user_id`? | Reason |
|-------|-----------------|--------|
| `meeting_done_items` | ✅ Yes | Personal achievement records |
| `meeting_resource_signals` | ✅ Yes | Personal constraint data |
| `meeting_agendas` | ✅ Yes | Personal meeting preparation |
| `agenda_items` | ❌ No | Scoped through `agenda_id` |
| `deferred_items` | ✅ Yes | Personal pipeline |
| `opportunity_pipeline` | ✅ Yes | Personal pipeline |
| `deferral_reviews` | ✅ Yes | Personal review history |
| `project_budgets` | ✅ Yes | Personal financial data |
| `time_entries` | ✅ Yes | Personal time logs |
| `task_quality_gates` | ✅ Yes | Personal quality records |

### Section 3 — Updated Migrations

**Note:** All new top-level tables follow the same pattern as existing tables:
- `user_id` as the second column after `id`
- `constrained()->cascadeOnDelete()`
- `HasTenant` trait applied to the model

No structural changes to the tenancy architecture are required.

---

## 4. Filament Resources

**File:** `solas-run-filament-resources.md`

### Generate New Resources

**Add to "Generate All Resources" section:**

```bash
php artisan make:filament-resource ClientMeeting --generate
php artisan make:filament-resource MeetingAgenda --generate
php artisan make:filament-resource MeetingDoneItem --generate
php artisan make:filament-resource DeferredItem --generate
php artisan make:filament-resource OpportunityPipeline --generate
php artisan make:filament-resource ProjectBudget --generate
php artisan make:filament-resource TimeEntry --generate
```

### ClientMeetingResource — Updated Form

**Replace the existing `ClientMeetingResource` form with the tabbed version** defined in `solas-run-meeting-intelligence.md` Section 10. Key additions:

- `client_type` selector (External Client / Myself)
- `transcription_status` read-only indicator
- Tabs: Meeting Details / Transcript / Scope & Actions
- Relation managers: `DoneItemsRelationManager`, `ResourceSignalsRelationManager`, `ScopeItemsRelationManager`

### New Navigation Items

**Add to Navigation Registration section:**

| Group | New Resources | Sort |
|-------|--------------|------|
| Goals & Projects | Client Meetings | 5 |
| Goals & Projects | Meeting Agendas | 6 |
| Goals & Projects | Someday / Maybe | 7 |
| Goals & Projects | Opportunity Pipeline | 8 |
| Goals & Projects | Budgets | 9 |
| Goals & Projects | Time Entries | 10 |

### Done Item Table

The `MeetingDoneItemResource` is primarily accessed via the `ClientMeeting` relation manager, but a standalone view is useful for portfolio/testimonial tracking:

```php
protected static ?string $navigationIcon    = 'heroicon-o-check-badge';
protected static ?string $navigationGroup   = 'Goals & Projects';
protected static ?string $navigationLabel   = 'Done & Delivered';
protected static ?int    $navigationSort    = 11;

// Key table columns:
// - meeting.meeting_date → When
// - title → What was delivered
// - outcome_metric → Quantified result
// - client_quote → Their words
// - save_as_testimonial → Flag for reuse
// - value_delivered → $ impact
```

---

## 5. Dashboard Widgets

**File:** `solas-run-dashboard-widgets.md`

### Section 9 — Dashboard Layout & Registration

**Add two new widgets:**

```php
// Register in AdminPanelProvider->widgets()
\App\Filament\Widgets\OpportunityPipelineWidget::class,  // ← NEW (from deferral doc)
\App\Filament\Widgets\DoneDeliveredWidget::class,         // ← NEW (see below)
```

### New Widget: DoneDeliveredWidget

A motivational widget showing recent completed work with outcomes — a counterbalance to the forward-looking goal progress bars. Shows the value already delivered, not just the value still to come.

```php
// app/Filament/Widgets/DoneDeliveredWidget.php

class DoneDeliveredWidget extends BaseWidget
{
    protected static ?int $sort = 9;
    protected int | string | array $columnSpan = 1;
    protected static string $view = 'filament.widgets.done-delivered-widget';

    public function getViewData(): array
    {
        $recentDoneItems = \App\Models\MeetingDoneItem::with('meeting')
            ->latest()
            ->limit(5)
            ->get();

        $totalValueDelivered = \App\Models\MeetingDoneItem::sum('value_delivered');
        $thisMonthDone       = \App\Models\Task::where('status', 'done')
            ->whereMonth('updated_at', now()->month)
            ->count();

        return compact('recentDoneItems', 'totalValueDelivered', 'thisMonthDone');
    }
}
```

**Blade view displays:**
- "Value Delivered This Month" total
- Tasks completed this month count
- Last 5 done items with outcome metrics and client quotes
- Links to full Done & Delivered resource

### Updated Dashboard Layout Diagram

```
┌─────────────────────────────────────────────────────────┐
│                   DayThemeWidget                        │
└─────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────┐
│                  AiIntentionWidget                      │
└─────────────────────────────────────────────────────────┘
┌───────────────────────────────────┬─────────────────────┐
│     MorningChecklistWidget        │ TimeBlockTimeline   │
└───────────────────────────────────┴─────────────────────┘
┌───────────────────────────────────┬──────────┬──────────┐
│      GoalProgressWidget           │  Habit   │ Streak   │
│                                   │   Ring   │Highlights│
└───────────────────────────────────┴──────────┴──────────┘
┌──────────────────────┬──────────────────────────────────┐
│  OpportunityPipeline │      DoneDeliveredWidget         │
│      Widget          │   (recent outcomes & value)      │
└──────────────────────┴───────────────────────────────────┘
```

---

## 6. Task Decomposition

**File:** `solas-run-task-decomposition.md`

### Section 1 — The Methodology

**Add after "The Task Tree":**

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

### Section 3 — New Tables

**Add to the list:**

> `meeting_done_items` — While not strictly part of task decomposition, done items created from meeting transcripts can be linked back to tasks, providing a feedback loop: the task tree shows what was planned; done items show what was confirmed delivered and what impact it had.

---

### Section 6 — TaskTreeService

**Add new method:**

```php
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
```

---

### Section 9 — Client Meeting & Scope Resource

**Replace the `ClientMeetingResource` form schema** with the tabbed version from `solas-run-meeting-intelligence.md` Section 10.

**Add to the resource:**

```php
public static function getRelationManagers(): array
{
    return [
        ScopeItemsRelationManager::class,
        DoneItemsRelationManager::class,      // ← NEW
        ResourceSignalsRelationManager::class, // ← NEW
    ];
}
```

---

### Section 12 — AI Integration Points

**Replace "Scope Analysis from Meeting Transcript" with the full `MeetingIntelligenceService::analyze()` method** from `solas-run-meeting-intelligence.md` Section 8. The single-call approach replaces the simpler scope-only analysis.

**Update "Scope Creep Guard" to include internal scope creep:**

```php
// Add to checkTaskScope() — internal version

if ($project->client_type === 'self') {
    // For internal goals, scope creep = taking on more than
    // current personal resources allow
    $resourceStatus = $this->assessPersonalResources();
    // ... check if new task is within current capacity
}
```

---

## 7. Deferral Pipeline

**File:** `solas-run-deferral-pipeline.md`

### Section 1 — The GTD Deferral Philosophy

**Add new subsection after "The Deferral Landscape":**

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

### Section 2 — Deferral Types & Taxonomy

**Update "Deferral Reasons" table — add `personal` detail:**

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

**Update "Opportunity Types" table — add `personal-development` row:**

| Type | Description |
|------|-------------|
| `personal-development` | A skill, capability, or experience you want to develop when resourced |

---

### Section 3 — New & Updated Tables

**Update `deferred_items` description:**

> Add `client_type` column (`external` | `self`) to distinguish between client deferrals and personal deferrals. This affects how the system displays and prompts for review — personal deferrals use resource readiness language; client deferrals use sales pipeline language.

```php
// Add to create_deferred_items_table migration
$table->enum('client_type', ['external', 'self'])->default('external')->after('user_id');

// Add resource readiness fields
$table->json('resource_requirements')->nullable()->after('why_it_matters');
// Structure: [{ "type": "time", "description": "...", "estimated_ready": "..." }]

$table->boolean('resource_check_done')->default(false);
// Has the user explicitly reviewed whether resources are now available?
```

---

### Section 6 — DeferralService

**Update `captureIdea()` to handle personal goals:**

```php
/**
 * Capture a personal goal as a deferred item.
 * Uses resource readiness language instead of sales pipeline language.
 */
public function capturePersonalGoal(
    string  $title,
    string  $lifeArea,
    string  $whyItMatters,
    array   $resourceRequirements = [],
    ?string $revisitTrigger = null
): DeferredItem {

    return DeferredItem::create([
        'user_id'              => auth()->id(),
        'title'                => $title,
        'why_it_matters'       => $whyItMatters,
        'client_type'          => 'self',
        'opportunity_type'     => 'personal-goal',
        'deferral_reason'      => $this->detectDeferralReason($resourceRequirements),
        'resource_requirements' => $resourceRequirements,
        'revisit_trigger'      => $revisitTrigger,
        'status'               => 'someday',
        'deferred_on'          => today(),
        'client_name'          => 'Internal — ' . auth()->user()->name,
    ]);
}

private function detectDeferralReason(array $requirements): string
{
    // If multiple resources needed, default to 'priority'
    if (count($requirements) > 1) return 'priority';

    return match($requirements[0]['type'] ?? 'priority') {
        'budget'       => 'budget',
        'time'         => 'timeline',
        'technology'   => 'technology',
        'capability'   => 'client-not-ready',  // You = the client not ready
        'energy'       => 'personal',
        'readiness'    => 'personal',
        'dependency'   => 'awaiting-decision',
        default        => 'priority',
    };
}
```

---

### Section 8 — Filament Resources

**Update `DeferredItemResource` table — add columns:**

```php
// Add to table columns
TextColumn::make('client_type')
    ->label('Type')
    ->formatStateUsing(fn ($state) => $state === 'self' ? '🪞 Internal' : '🤝 Client')
    ->badge()
    ->color(fn ($state) => $state === 'self' ? 'info' : 'success'),
```

**Update `DeferredItemResource` form — add to Classification section:**

```php
Select::make('client_type')
    ->label('This is for')
    ->options([
        'external' => '🤝 An External Client',
        'self'     => '🪞 Myself (Personal Goal)',
    ])
    ->default('external')
    ->live(),
```

**Add "Personal Resources Required" section, visible when `client_type === 'self'`:**

```php
Section::make('Personal Resources Required')
    ->visible(fn ($get) => $get('client_type') === 'self')
    ->schema([
        \Filament\Forms\Components\Repeater::make('resource_requirements')
            ->schema([
                Select::make('type')
                    ->options([
                        'time'        => '⏰ Time / Bandwidth',
                        'budget'      => '💰 Money',
                        'technology'  => '⚙️ Technology',
                        'capability'  => '🧠 Skill / Capability',
                        'energy'      => '🔋 Energy',
                        'readiness'   => '🧘 Readiness',
                        'dependency'  => '🔗 Dependency',
                    ])
                    ->required(),
                TextInput::make('description')
                    ->placeholder('What specifically do you need?'),
                TextInput::make('estimated_ready')
                    ->label('Estimated Available')
                    ->placeholder('e.g. Q2 next year, After current project'),
            ])
            ->columns(3)
            ->addActionLabel('Add Resource Requirement')
            ->columnSpanFull(),
    ]),
```

---

### Section 9 — AI Integration Points

**Update "1. Opportunity Analysis" to handle personal goals:**

When `client_type === 'self'`, the AI prompt changes:

```php
if ($item->client_type === 'self') {
    $prompt = <<<PROMPT
A user has deferred a personal goal. Analyze whether the time is right to revisit it.

GOAL: "{$item->title}"
WHY IT MATTERS: {$item->why_it_matters}
DEFERRED BECAUSE: {$item->deferral_reason}
RESOURCE REQUIREMENTS: {$this->formatResourceRequirements($item)}
DEFERRED ON: {$item->deferred_on->diffForHumans()}

Write a brief personal readiness assessment covering:
1. What specifically was missing when this was deferred
2. Signs that the resource constraint might be lifting
3. What activating this goal would require in the next 30 days
4. Whether this is the right season to start

Be honest. Speak to the person directly. Not everything deferred should be activated.
PROMPT;
}
```

---

### Section 10 — The Review Cadence

**Add to "Automatic Resurface Rules" table:**

| Condition | Action |
|-----------|--------|
| Personal goal's `resource_requirements` estimate date is reached | Flagged for resource readiness review |
| Related capability goal is marked achieved | Linked personal goals resurface |
| Finance goal reaches a milestone | Budget-deferred items resurface |
| Energy/health metrics improve over 4+ weeks | Readiness-deferred items surface in weekly review |

---

## Summary of New Files

| File | Status | Purpose |
|------|--------|---------|
| `solas-run-blueprint.md` | Updated per section 1 above | Core vision and data model |
| `solas-run-laravel-setup.md` | Updated per section 2 above | New packages, migrations, models |
| `solas-run-multitenancy.md` | Updated per section 3 above | New tables needing `user_id` |
| `solas-run-filament-resources.md` | Updated per section 4 above | New resources and relation managers |
| `solas-run-dashboard-widgets.md` | Updated per section 5 above | New widgets, updated layout |
| `solas-run-task-decomposition.md` | Updated per section 6 above | Self-as-client trees, done items |
| `solas-run-deferral-pipeline.md` | Updated per section 7 above | Personal resources, self-as-client |
| `solas-run-meeting-intelligence.md` | **NEW** | Transcription API, agenda builder, extraction |

---

*Solas Rún • v1.1 Amendments • Meeting Intelligence & Self-as-Client Model*
