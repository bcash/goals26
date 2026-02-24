# SOLAS RÚN
### *Laravel Project Setup & Migrations*
**Technical Reference v1.2**

---

## Table of Contents

1. [Requirements](#1-requirements)
2. [Project Creation](#2-project-creation)
3. [Environment Configuration](#3-environment-configuration)
4. [Install Filament v3](#4-install-filament-v3)
5. [Install Supporting Packages](#5-install-supporting-packages)
6. [Directory & File Structure](#6-directory--file-structure)
7. [Migrations](#7-migrations)
8. [Seeders](#8-seeders)
9. [Models](#9-models)
10. [Run the Application](#10-run-the-application)

---

## 1. Requirements

| Requirement | Version |
|-------------|---------|
| PHP | 8.2+ |
| Composer | 2.x |
| Laravel | 11.x |
| Filament | 3.x |
| Node.js | 20+ |
| MySQL | 8.0+ (or PostgreSQL 15+) |

---

## 2. Project Creation

```bash
composer create-project laravel/laravel solas-run
cd solas-run
```

---

## 3. Environment Configuration

Copy and edit your `.env` file:

```bash
cp .env.example .env
php artisan key:generate
```

Update the following values in `.env`:

```dotenv
APP_NAME="Solas Rún"
APP_ENV=local
APP_URL=http://solas-run.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=solas_run
DB_USERNAME=root
DB_PASSWORD=your_password
```

Create the database:

```sql
CREATE DATABASE solas_run CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## 4. Install Filament v3

```bash
composer require filament/filament:"^3.3"
php artisan filament:install --panels
```

When prompted:
- Panel ID: `admin`
- Accept all defaults

Create your admin user:

```bash
php artisan make:filament-user
```

Publish Filament assets:

```bash
php artisan vendor:publish --tag=filament-config
php artisan vendor:publish --tag=filament-translations
```

---

## 5. Install Supporting Packages

### Charts & Widgets

```bash
composer require filament/widgets
```

### AI (OpenAI / Anthropic via HTTP client — built-in to Laravel)

No additional package required. We will use Laravel's `Http` facade to call the AI API directly, keeping the integration clean and dependency-light.

### Markdown Editor for Journal

```bash
php artisan filament:install --forms
```

Filament's built-in `MarkdownEditor` component will handle journal entries.

#### Granola MCP (Meeting Notes)

No additional Composer packages required. Solas Rún communicates with the Granola MCP server via Laravel's built-in `Http` facade.

Add to `.env`:

```dotenv
# Granola MCP — meeting notes and transcripts
GRANOLA_MCP_URL=http://localhost:3333
GRANOLA_MCP_TRANSPORT=http
```

Add to `config/services.php`:

```php
'granola' => [
    'mcp_url'   => env('GRANOLA_MCP_URL', 'http://localhost:3333'),
    'transport' => env('GRANOLA_MCP_TRANSPORT', 'http'),
],
```

Run the Granola MCP server alongside your Laravel app:

```bash
npx granola-mcp --transport http --port 3333
```

### Spatie Laravel Settings *(User Preferences)*

```bash
composer require spatie/laravel-settings
php artisan vendor:publish --provider="Spatie\LaravelSettings\LaravelSettingsServiceProvider"
php artisan migrate
```

### Carbon is included with Laravel

No additional installation needed. Used throughout for date/time calculations, streak logic, and weekly rhythm detection.

---

## 6. Directory & File Structure

After setup, the relevant application structure will be:

```
app/
├── Filament/
│   ├── Pages/
│   │   ├── Dashboard.php
│   │   ├── TodayPlan.php
│   │   └── AiStudio.php
│   ├── Resources/
│   │   ├── LifeAreaResource.php
│   │   ├── GoalResource.php
│   │   ├── MilestoneResource.php
│   │   ├── ProjectResource.php
│   │   ├── TaskResource.php
│   │   ├── HabitResource.php
│   │   ├── HabitLogResource.php
│   │   ├── DailyPlanResource.php
│   │   ├── TimeBlockResource.php
│   │   ├── JournalEntryResource.php
│   │   ├── WeeklyReviewResource.php
│   │   └── AiInteractionResource.php
│   └── Widgets/
│       ├── DayThemeWidget.php
│       ├── MorningChecklistWidget.php
│       ├── TimeBlockTimelineWidget.php
│       ├── GoalProgressWidget.php
│       ├── HabitRingWidget.php
│       ├── AiIntentionWidget.php
│       └── StreakHighlightsWidget.php
├── Http/
│   └── Controllers/
├── Models/
│   ├── LifeArea.php
│   ├── Goal.php
│   ├── Milestone.php
│   ├── Project.php
│   ├── Task.php
│   ├── Habit.php
│   ├── HabitLog.php
│   ├── DailyPlan.php
│   ├── TimeBlock.php
│   ├── JournalEntry.php
│   ├── WeeklyReview.php
│   └── AiInteraction.php
├── Services/
│   ├── AiService.php
│   ├── HabitStreakService.php
│   ├── DailyPlanService.php
│   ├── GoalProgressService.php
│   ├── MeetingIntelligenceService.php   ← NEW
│   ├── AgendaService.php                ← NEW
│   ├── GranolaMcpClient.php             ← NEW
│   └── GranolaSyncService.php           ← NEW
├── Jobs/
│   ├── AnalyzeDeferredOpportunity.php
│   └── AnalyzeMeetingTranscript.php    ← NEW
database/
├── migrations/
│   ├── 2024_01_01_000001_create_life_areas_table.php
│   ├── 2024_01_01_000002_create_goals_table.php
│   ├── 2024_01_01_000003_create_milestones_table.php
│   ├── 2024_01_01_000004_create_projects_table.php
│   ├── 2024_01_01_000005_create_tasks_table.php
│   ├── 2024_01_01_000006_create_habits_table.php
│   ├── 2024_01_01_000007_create_habit_logs_table.php
│   ├── 2024_01_01_000008_create_daily_plans_table.php
│   ├── 2024_01_01_000009_create_time_blocks_table.php
│   ├── 2024_01_01_000010_create_journal_entries_table.php
│   ├── 2024_01_01_000011_create_weekly_reviews_table.php
│   ├── 2024_01_01_000012_create_ai_interactions_table.php
│   ├── 2024_01_01_000013_add_transcription_fields_to_client_meetings_table.php
│   ├── 2024_01_01_000014_add_deferral_fields_to_tasks_table.php
│   ├── 2024_01_01_000015_create_task_quality_gates_table.php
│   ├── 2024_01_01_000016_create_deferred_items_table.php
│   ├── 2024_01_01_000017_create_opportunity_pipeline_table.php
│   ├── 2024_01_01_000018_create_deferral_reviews_table.php
│   ├── 2024_01_01_000019_create_meeting_done_items_table.php
│   ├── 2024_01_01_000020_create_meeting_resource_signals_table.php
│   ├── 2024_01_01_000021_create_meeting_agendas_table.php
│   ├── 2024_01_01_000022_create_agenda_items_table.php
│   ├── 2024_01_01_000023_create_project_budgets_table.php
│   └── 2024_01_01_000024_create_time_entries_table.php
└── seeders/
    ├── DatabaseSeeder.php
    └── LifeAreaSeeder.php
```

---

## 7. Migrations

Run each `make:migration` command, then replace the generated `up()` body with the schema below.

---

### Migration 1 — `life_areas`

```bash
php artisan make:migration create_life_areas_table
```

```php
public function up(): void
{
    Schema::create('life_areas', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('icon')->default('heroicon-o-star');  // Heroicon name or emoji
        $table->string('color_hex', 7)->default('#C9A84C'); // CSS hex color
        $table->text('description')->nullable();
        $table->unsignedSmallInteger('sort_order')->default(0);
        $table->timestamps();
    });
}
```

---

### Migration 2 — `goals`

```bash
php artisan make:migration create_goals_table
```

```php
public function up(): void
{
    Schema::create('goals', function (Blueprint $table) {
        $table->id();
        $table->foreignId('life_area_id')->constrained()->cascadeOnDelete();
        $table->string('title');
        $table->text('description')->nullable();
        $table->text('why')->nullable();  // The motivation — shown during planning
        $table->enum('horizon', ['90-day', '1-year', '3-year', 'lifetime'])
              ->default('1-year');
        $table->enum('status', ['active', 'paused', 'achieved', 'abandoned'])
              ->default('active');
        $table->date('target_date')->nullable();
        $table->unsignedTinyInteger('progress_percent')->default(0); // 0–100
        $table->timestamps();
    });
}
```

---

### Migration 3 — `milestones`

```bash
php artisan make:migration create_milestones_table
```

```php
public function up(): void
{
    Schema::create('milestones', function (Blueprint $table) {
        $table->id();
        $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
        $table->string('title');
        $table->date('due_date')->nullable();
        $table->enum('status', ['pending', 'complete'])->default('pending');
        $table->unsignedSmallInteger('sort_order')->default(0);
        $table->timestamps();
    });
}
```

---

### Migration 4 — `projects`

```bash
php artisan make:migration create_projects_table
```

```php
public function up(): void
{
    Schema::create('projects', function (Blueprint $table) {
        $table->id();
        $table->foreignId('life_area_id')->constrained()->cascadeOnDelete();
        $table->foreignId('goal_id')->nullable()->constrained()->nullOnDelete();
        $table->string('name');
        $table->text('description')->nullable();
        $table->enum('status', ['active', 'on-hold', 'complete', 'archived'])
              ->default('active');
        $table->string('client_name')->nullable();
        $table->date('due_date')->nullable();
        $table->string('color_hex', 7)->nullable();
        $table->timestamps();
    });
}
```

---

### Migration 5 — `tasks`

```bash
php artisan make:migration create_tasks_table
```

```php
public function up(): void
{
    Schema::create('tasks', function (Blueprint $table) {
        $table->id();
        $table->foreignId('life_area_id')->constrained()->cascadeOnDelete();
        $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('goal_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('milestone_id')->nullable()->constrained()->nullOnDelete();
        $table->string('title');
        $table->text('notes')->nullable();
        $table->enum('status', ['todo', 'in-progress', 'done', 'deferred'])
              ->default('todo');
        $table->enum('priority', ['low', 'medium', 'high', 'critical'])
              ->default('medium');
        $table->date('due_date')->nullable();
        $table->date('scheduled_date')->nullable();
        $table->unsignedSmallInteger('time_estimate_minutes')->nullable();
        $table->boolean('is_daily_action')->default(false);
        $table->timestamps();
    });
}
```

---

### Migration 6 — `habits`

```bash
php artisan make:migration create_habits_table
```

```php
public function up(): void
{
    Schema::create('habits', function (Blueprint $table) {
        $table->id();
        $table->foreignId('life_area_id')->constrained()->cascadeOnDelete();
        $table->string('title');
        $table->text('description')->nullable();
        $table->enum('frequency', ['daily', 'weekdays', 'weekly', 'custom'])
              ->default('daily');
        $table->json('target_days')->nullable(); // [0,1,2,3,4,5,6] — 0=Sun
        $table->enum('time_of_day', ['morning', 'afternoon', 'evening', 'anytime'])
              ->default('anytime');
        $table->enum('status', ['active', 'paused'])->default('active');
        $table->unsignedSmallInteger('streak_current')->default(0);
        $table->unsignedSmallInteger('streak_best')->default(0);
        $table->date('started_at');
        $table->timestamps();
    });
}
```

---

### Migration 7 — `habit_logs`

```bash
php artisan make:migration create_habit_logs_table
```

```php
public function up(): void
{
    Schema::create('habit_logs', function (Blueprint $table) {
        $table->id();
        $table->foreignId('habit_id')->constrained()->cascadeOnDelete();
        $table->date('logged_date');
        $table->enum('status', ['completed', 'skipped', 'missed'])->default('completed');
        $table->string('note')->nullable();
        $table->timestamps();

        $table->unique(['habit_id', 'logged_date']); // One log per habit per day
    });
}
```

---

### Migration 8 — `daily_plans`

```bash
php artisan make:migration create_daily_plans_table
```

```php
public function up(): void
{
    Schema::create('daily_plans', function (Blueprint $table) {
        $table->id();
        $table->date('plan_date')->unique();
        $table->string('day_theme')->nullable();
        $table->text('morning_intention')->nullable();

        // Top 3 priorities — nullable FKs to tasks
        $table->foreignId('top_priority_1')
              ->nullable()->constrained('tasks')->nullOnDelete();
        $table->foreignId('top_priority_2')
              ->nullable()->constrained('tasks')->nullOnDelete();
        $table->foreignId('top_priority_3')
              ->nullable()->constrained('tasks')->nullOnDelete();

        // AI content
        $table->text('ai_morning_prompt')->nullable();
        $table->text('ai_evening_summary')->nullable();

        // Evening ratings (1–5)
        $table->unsignedTinyInteger('energy_rating')->nullable();
        $table->unsignedTinyInteger('focus_rating')->nullable();
        $table->unsignedTinyInteger('progress_rating')->nullable();

        $table->text('evening_reflection')->nullable();
        $table->enum('status', ['draft', 'active', 'reviewed'])->default('draft');
        $table->timestamps();
    });
}
```

---

### Migration 9 — `time_blocks`

```bash
php artisan make:migration create_time_blocks_table
```

```php
public function up(): void
{
    Schema::create('time_blocks', function (Blueprint $table) {
        $table->id();
        $table->foreignId('daily_plan_id')->constrained()->cascadeOnDelete();
        $table->string('title');
        $table->enum('block_type', ['deep-work', 'admin', 'meeting', 'personal', 'buffer'])
              ->default('deep-work');
        $table->time('start_time');
        $table->time('end_time');
        $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
        $table->text('notes')->nullable();
        $table->string('color_hex', 7)->nullable();
        $table->timestamps();
    });
}
```

---

### Migration 10 — `journal_entries`

```bash
php artisan make:migration create_journal_entries_table
```

```php
public function up(): void
{
    Schema::create('journal_entries', function (Blueprint $table) {
        $table->id();
        $table->date('entry_date');
        $table->enum('entry_type', ['morning', 'evening', 'weekly', 'freeform'])
              ->default('freeform');
        $table->longText('content');
        $table->unsignedTinyInteger('mood')->nullable(); // 1–5
        $table->json('tags')->nullable();
        $table->text('ai_insights')->nullable();
        $table->timestamps();
    });
}
```

---

### Migration 11 — `weekly_reviews`

```bash
php artisan make:migration create_weekly_reviews_table
```

```php
public function up(): void
{
    Schema::create('weekly_reviews', function (Blueprint $table) {
        $table->id();
        $table->date('week_start_date')->unique(); // Always Monday
        $table->text('wins')->nullable();
        $table->text('friction')->nullable();
        $table->json('outcomes_met')->nullable();
        // Structure: { "creative": 4, "business": 3, "health": 2, ... }
        $table->unsignedTinyInteger('overall_score')->nullable(); // 1–5
        $table->text('ai_analysis')->nullable();
        $table->text('next_week_focus')->nullable();
        $table->timestamps();
    });
}
```

---

### Migration 12 — `ai_interactions`

```bash
php artisan make:migration create_ai_interactions_table
```

```php
public function up(): void
{
    Schema::create('ai_interactions', function (Blueprint $table) {
        $table->id();
        $table->enum('interaction_type', [
            'daily-morning',
            'daily-evening',
            'weekly',
            'goal-breakdown',
            'freeform',
        ]);
        $table->json('context_json')->nullable(); // Snapshot of data sent to AI
        $table->text('prompt');
        $table->longText('response')->nullable();
        $table->foreignId('daily_plan_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignId('goal_id')->nullable()->constrained()->nullOnDelete();
        $table->unsignedSmallInteger('tokens_used')->nullable();
        $table->string('model_used')->nullable(); // e.g. "claude-sonnet-4-6"
        $table->timestamps();
    });
}
```

---

## 8. Seeders

### DatabaseSeeder.php

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LifeAreaSeeder::class,
        ]);
    }
}
```

---

### LifeAreaSeeder.php

```bash
php artisan make:seeder LifeAreaSeeder
```

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LifeAreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            [
                'name'        => 'Creative',
                'icon'        => '🎨',
                'color_hex'   => '#7C3AED', // Purple
                'description' => 'Writing, music, TV production, and all creative output.',
                'sort_order'  => 1,
            ],
            [
                'name'        => 'Business',
                'icon'        => '💼',
                'color_hex'   => '#1D4ED8', // Blue
                'description' => 'Client work, team management, revenue, and growth.',
                'sort_order'  => 2,
            ],
            [
                'name'        => 'Health',
                'icon'        => '💚',
                'color_hex'   => '#059669', // Green
                'description' => 'Physical wellness, mental health, energy, sleep, and nutrition.',
                'sort_order'  => 3,
            ],
            [
                'name'        => 'Family',
                'icon'        => '👨‍👩‍👧',
                'color_hex'   => '#D97706', // Amber
                'description' => 'Relationships, presence, shared experiences, and legacy.',
                'sort_order'  => 4,
            ],
            [
                'name'        => 'Growth',
                'icon'        => '📚',
                'color_hex'   => '#0891B2', // Cyan
                'description' => 'Learning, skills, reading, courses, and spiritual development.',
                'sort_order'  => 5,
            ],
            [
                'name'        => 'Finance',
                'icon'        => '💰',
                'color_hex'   => '#C9A84C', // Solas Rún gold
                'description' => 'Income, expenses, savings, investments, and financial goals.',
                'sort_order'  => 6,
            ],
        ];

        foreach ($areas as $area) {
            DB::table('life_areas')->updateOrInsert(
                ['name' => $area['name']],
                array_merge($area, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
```

---

## 9. Models

Create all models with their relationships. Run the make commands first, then add the relationship methods shown below.

```bash
php artisan make:model LifeArea
php artisan make:model Goal
php artisan make:model Milestone
php artisan make:model Project
php artisan make:model Task
php artisan make:model Habit
php artisan make:model HabitLog
php artisan make:model DailyPlan
php artisan make:model TimeBlock
php artisan make:model JournalEntry
php artisan make:model WeeklyReview
php artisan make:model AiInteraction
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

---

### LifeArea.php

```php
protected $fillable = ['name', 'icon', 'color_hex', 'description', 'sort_order'];

public function goals(): HasMany
{
    return $this->hasMany(Goal::class);
}

public function projects(): HasMany
{
    return $this->hasMany(Project::class);
}

public function tasks(): HasMany
{
    return $this->hasMany(Task::class);
}

public function habits(): HasMany
{
    return $this->hasMany(Habit::class);
}
```

---

### Goal.php

```php
protected $fillable = [
    'life_area_id', 'title', 'description', 'why',
    'horizon', 'status', 'target_date', 'progress_percent',
];

protected $casts = ['target_date' => 'date'];

public function lifeArea(): BelongsTo
{
    return $this->belongsTo(LifeArea::class);
}

public function milestones(): HasMany
{
    return $this->hasMany(Milestone::class);
}

public function tasks(): HasMany
{
    return $this->hasMany(Task::class);
}

public function projects(): HasMany
{
    return $this->hasMany(Project::class);
}

public function aiInteractions(): HasMany
{
    return $this->hasMany(AiInteraction::class);
}
```

---

### Milestone.php

```php
protected $fillable = ['goal_id', 'title', 'due_date', 'status', 'sort_order'];

protected $casts = ['due_date' => 'date'];

public function goal(): BelongsTo
{
    return $this->belongsTo(Goal::class);
}

public function tasks(): HasMany
{
    return $this->hasMany(Task::class);
}
```

---

### Project.php

```php
protected $fillable = [
    'life_area_id', 'goal_id', 'name', 'description',
    'status', 'client_name', 'due_date', 'color_hex',
];

protected $casts = ['due_date' => 'date'];

public function lifeArea(): BelongsTo
{
    return $this->belongsTo(LifeArea::class);
}

public function goal(): BelongsTo
{
    return $this->belongsTo(Goal::class);
}

public function tasks(): HasMany
{
    return $this->hasMany(Task::class);
}
```

---

### Task.php

```php
protected $fillable = [
    'life_area_id', 'project_id', 'goal_id', 'milestone_id',
    'title', 'notes', 'status', 'priority',
    'due_date', 'scheduled_date', 'time_estimate_minutes', 'is_daily_action',
];

protected $casts = [
    'due_date'       => 'date',
    'scheduled_date' => 'date',
    'is_daily_action' => 'boolean',
];

public function lifeArea(): BelongsTo
{
    return $this->belongsTo(LifeArea::class);
}

public function project(): BelongsTo
{
    return $this->belongsTo(Project::class);
}

public function goal(): BelongsTo
{
    return $this->belongsTo(Goal::class);
}

public function milestone(): BelongsTo
{
    return $this->belongsTo(Milestone::class);
}
```

---

### Habit.php

```php
protected $fillable = [
    'life_area_id', 'title', 'description', 'frequency',
    'target_days', 'time_of_day', 'status',
    'streak_current', 'streak_best', 'started_at',
];

protected $casts = [
    'target_days' => 'array',
    'started_at'  => 'date',
];

public function lifeArea(): BelongsTo
{
    return $this->belongsTo(LifeArea::class);
}

public function logs(): HasMany
{
    return $this->hasMany(HabitLog::class);
}

public function todayLog(): HasOne
{
    return $this->hasOne(HabitLog::class)
                ->whereDate('logged_date', today());
}
```

---

### HabitLog.php

```php
protected $fillable = ['habit_id', 'logged_date', 'status', 'note'];

protected $casts = ['logged_date' => 'date'];

public function habit(): BelongsTo
{
    return $this->belongsTo(Habit::class);
}
```

---

### DailyPlan.php

```php
protected $fillable = [
    'plan_date', 'day_theme', 'morning_intention',
    'top_priority_1', 'top_priority_2', 'top_priority_3',
    'ai_morning_prompt', 'ai_evening_summary',
    'energy_rating', 'focus_rating', 'progress_rating',
    'evening_reflection', 'status',
];

protected $casts = ['plan_date' => 'date'];

public function priority1(): BelongsTo
{
    return $this->belongsTo(Task::class, 'top_priority_1');
}

public function priority2(): BelongsTo
{
    return $this->belongsTo(Task::class, 'top_priority_2');
}

public function priority3(): BelongsTo
{
    return $this->belongsTo(Task::class, 'top_priority_3');
}

public function timeBlocks(): HasMany
{
    return $this->hasMany(TimeBlock::class)->orderBy('start_time');
}

public function aiInteractions(): HasMany
{
    return $this->hasMany(AiInteraction::class);
}

public static function today(): ?self
{
    return static::whereDate('plan_date', today())->first();
}

public static function todayOrCreate(): self
{
    return static::firstOrCreate(
        ['plan_date' => today()->toDateString()],
        ['status'    => 'draft']
    );
}
```

---

### TimeBlock.php

```php
protected $fillable = [
    'daily_plan_id', 'title', 'block_type',
    'start_time', 'end_time', 'task_id', 'project_id',
    'notes', 'color_hex',
];

public function dailyPlan(): BelongsTo
{
    return $this->belongsTo(DailyPlan::class);
}

public function task(): BelongsTo
{
    return $this->belongsTo(Task::class);
}

public function project(): BelongsTo
{
    return $this->belongsTo(Project::class);
}
```

---

### JournalEntry.php

```php
protected $fillable = [
    'entry_date', 'entry_type', 'content', 'mood', 'tags', 'ai_insights',
];

protected $casts = [
    'entry_date' => 'date',
    'tags'       => 'array',
];
```

---

### WeeklyReview.php

```php
protected $fillable = [
    'week_start_date', 'wins', 'friction', 'outcomes_met',
    'overall_score', 'ai_analysis', 'next_week_focus',
];

protected $casts = [
    'week_start_date' => 'date',
    'outcomes_met'    => 'array',
];
```

---

### AiInteraction.php

```php
protected $fillable = [
    'interaction_type', 'context_json', 'prompt', 'response',
    'daily_plan_id', 'goal_id', 'tokens_used', 'model_used',
];

protected $casts = ['context_json' => 'array'];

public function dailyPlan(): BelongsTo
{
    return $this->belongsTo(DailyPlan::class);
}

public function goal(): BelongsTo
{
    return $this->belongsTo(Goal::class);
}
```

---

## 10. Run the Application

Run all migrations and seed the database:

```bash
php artisan migrate --seed
```

Compile frontend assets:

```bash
npm install
npm run dev
```

Start the development server:

```bash
php artisan serve
```

Visit your Filament admin panel at:

```
http://solas-run.test/admin
```

Or if using `php artisan serve`:

```
http://127.0.0.1:8000/admin
```

Log in with the admin user you created during Filament installation.

---

### Verify Everything is Working

```bash
# Confirm all tables exist
php artisan tinker
>>> \Schema::getTableListing()

# Confirm life areas were seeded
>>> \App\Models\LifeArea::all()->pluck('name')
```

Expected output:

```
["Creative", "Business", "Health", "Family", "Growth", "Finance"]
```

---

### Useful Artisan Commands Reference

| Command | Purpose |
|---------|---------|
| `php artisan migrate` | Run pending migrations |
| `php artisan migrate:fresh --seed` | Drop all tables and re-run from scratch |
| `php artisan migrate:rollback` | Roll back the last batch of migrations |
| `php artisan db:seed` | Run seeders only |
| `php artisan make:filament-resource ModelName --generate` | Scaffold a full Filament resource |
| `php artisan filament:make-widget WidgetName` | Create a new dashboard widget |
| `php artisan make:service ServiceName` | Create a service class |
| `php artisan queue:work` | Process background jobs (transcript analysis, AI generation) |
| `php artisan make:job JobName` | Create a new queued job |

---

*Solas Rún • Version 1.2 • Laravel Setup & Migrations*
