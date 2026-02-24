# SOLAS RÚN
### *Multi-Tenancy Architecture*
**Technical Reference v1.1**

---

## Overview

Solas Rún is a **user-scoped multi-tenant application**. Each registered user is their own tenant — fully isolated data, their own goals, habits, plans, and journal. No user can see or access another user's data.

This document covers the tenancy model, updated migrations, Filament configuration, global scoping, authentication, registration flow, and subscription scaffolding.

---

## Table of Contents

1. [Tenancy Model](#1-tenancy-model)
2. [Authentication Setup](#2-authentication-setup)
3. [Updated Migrations — Adding `user_id`](#3-updated-migrations--adding-user_id)
4. [Filament Multi-Tenancy Configuration](#4-filament-multi-tenancy-configuration)
5. [Global Scopes](#5-global-scopes)
6. [Policies](#6-policies)
7. [Updated Models](#7-updated-models)
8. [Updated Seeders](#8-updated-seeders)
9. [Registration & Onboarding Flow](#9-registration--onboarding-flow)
10. [Subscription & Access Control](#10-subscription--access-control)
11. [Security Checklist](#11-security-checklist)

---

## 1. Tenancy Model

### Approach: User-as-Tenant

Solas Rún uses **user-scoped tenancy** — the simplest, most robust approach for a personal productivity application where:

- Each user owns their own data exclusively
- There are no shared workspaces or teams (in v1)
- All data isolation is enforced at the database query level via `user_id`

### What This Means in Practice

Every table that holds personal data gets a `user_id` foreign key. Every Eloquent query is scoped to the authenticated user. Filament is configured to enforce this automatically.

### Tables That Need `user_id`

| Table | Needs `user_id`? | Reason |
|-------|-----------------|--------|
| `life_areas` | ✅ Yes | Each user customizes their own areas |
| `goals` | ✅ Yes | Personal goals |
| `milestones` | ❌ No | Scoped through `goal_id` → `user_id` |
| `projects` | ✅ Yes | Personal/client projects |
| `tasks` | ✅ Yes | Personal tasks |
| `habits` | ✅ Yes | Personal habits |
| `habit_logs` | ❌ No | Scoped through `habit_id` → `user_id` |
| `daily_plans` | ✅ Yes | Personal daily plans |
| `time_blocks` | ❌ No | Scoped through `daily_plan_id` → `user_id` |
| `journal_entries` | ✅ Yes | Personal journal |
| `weekly_reviews` | ✅ Yes | Personal reviews |
| `ai_interactions` | ✅ Yes | Personal AI history |
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

> **Rule:** Direct top-level resources get `user_id`. Child records are protected through their parent's `user_id` constraint.

---

## 2. Authentication Setup

### Install Laravel Breeze (API + Blade)

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
npm install && npm run dev
php artisan migrate
```

This provides: registration, login, password reset, email verification, and profile management out of the box.

### Update the `users` Table Migration

Add these fields to the existing `create_users_table` migration (or create a new migration to add columns):

```bash
php artisan make:migration add_profile_fields_to_users_table
```

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->string('timezone')->default('UTC')->after('email');
        $table->enum('subscription_status', [
            'trial',
            'active',
            'cancelled',
            'expired',
        ])->default('trial')->after('timezone');
        $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
        $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
        $table->string('stripe_customer_id')->nullable()->after('subscription_ends_at');
        $table->boolean('onboarding_complete')->default(false)->after('stripe_customer_id');
    });
}
```

### User Model Updates

```php
// app/Models/User.php

protected $fillable = [
    'name',
    'email',
    'password',
    'timezone',
    'subscription_status',
    'trial_ends_at',
    'subscription_ends_at',
    'stripe_customer_id',
    'onboarding_complete',
];

protected $casts = [
    'email_verified_at'    => 'datetime',
    'trial_ends_at'        => 'datetime',
    'subscription_ends_at' => 'datetime',
    'onboarding_complete'  => 'boolean',
    'password'             => 'hashed',
];

// Relationships
public function lifeAreas(): HasMany
{
    return $this->hasMany(LifeArea::class);
}

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

public function dailyPlans(): HasMany
{
    return $this->hasMany(DailyPlan::class);
}

public function journalEntries(): HasMany
{
    return $this->hasMany(JournalEntry::class);
}

public function weeklyReviews(): HasMany
{
    return $this->hasMany(WeeklyReview::class);
}

public function aiInteractions(): HasMany
{
    return $this->hasMany(AiInteraction::class);
}

// Subscription helpers
public function isOnTrial(): bool
{
    return $this->subscription_status === 'trial'
        && $this->trial_ends_at
        && $this->trial_ends_at->isFuture();
}

public function hasActiveAccess(): bool
{
    return in_array($this->subscription_status, ['trial', 'active'])
        && ($this->isOnTrial() || (
            $this->subscription_ends_at &&
            $this->subscription_ends_at->isFuture()
        ));
}
```

---

## 3. Updated Migrations — Adding `user_id`

Add `user_id` to each top-level table. You can either update the original migration files (if starting fresh) or create new `add_user_id_to_*` migrations.

### Recommended: Start Fresh with `user_id` Included

Update each migration's `up()` method to include `user_id` as the **second column** after `id`:

---

### `life_areas` — Updated

```php
Schema::create('life_areas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('icon')->default('heroicon-o-star');
    $table->string('color_hex', 7)->default('#C9A84C');
    $table->text('description')->nullable();
    $table->unsignedSmallInteger('sort_order')->default(0);
    $table->timestamps();

    $table->index('user_id');
});
```

---

### `goals` — Updated

```php
Schema::create('goals', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('life_area_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->text('description')->nullable();
    $table->text('why')->nullable();
    $table->enum('horizon', ['90-day', '1-year', '3-year', 'lifetime'])->default('1-year');
    $table->enum('status', ['active', 'paused', 'achieved', 'abandoned'])->default('active');
    $table->date('target_date')->nullable();
    $table->unsignedTinyInteger('progress_percent')->default(0);
    $table->timestamps();

    $table->index('user_id');
});
```

---

### `projects` — Updated

```php
Schema::create('projects', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('life_area_id')->constrained()->cascadeOnDelete();
    $table->foreignId('goal_id')->nullable()->constrained()->nullOnDelete();
    $table->string('name');
    $table->text('description')->nullable();
    $table->enum('status', ['active', 'on-hold', 'complete', 'archived'])->default('active');
    $table->string('client_name')->nullable();
    $table->date('due_date')->nullable();
    $table->string('color_hex', 7)->nullable();
    $table->timestamps();

    $table->index('user_id');
});
```

---

### `tasks` — Updated

```php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('life_area_id')->constrained()->cascadeOnDelete();
    $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('goal_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('milestone_id')->nullable()->constrained()->nullOnDelete();
    $table->string('title');
    $table->text('notes')->nullable();
    $table->enum('status', ['todo', 'in-progress', 'done', 'deferred'])->default('todo');
    $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
    $table->date('due_date')->nullable();
    $table->date('scheduled_date')->nullable();
    $table->unsignedSmallInteger('time_estimate_minutes')->nullable();
    $table->boolean('is_daily_action')->default(false);
    $table->timestamps();

    $table->index('user_id');
});
```

---

### `habits` — Updated

```php
Schema::create('habits', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->foreignId('life_area_id')->constrained()->cascadeOnDelete();
    $table->string('title');
    $table->text('description')->nullable();
    $table->enum('frequency', ['daily', 'weekdays', 'weekly', 'custom'])->default('daily');
    $table->json('target_days')->nullable();
    $table->enum('time_of_day', ['morning', 'afternoon', 'evening', 'anytime'])->default('anytime');
    $table->enum('status', ['active', 'paused'])->default('active');
    $table->unsignedSmallInteger('streak_current')->default(0);
    $table->unsignedSmallInteger('streak_best')->default(0);
    $table->date('started_at');
    $table->timestamps();

    $table->index('user_id');
});
```

---

### `daily_plans` — Updated

```php
Schema::create('daily_plans', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->date('plan_date');
    $table->string('day_theme')->nullable();
    $table->text('morning_intention')->nullable();
    $table->foreignId('top_priority_1')->nullable()->constrained('tasks')->nullOnDelete();
    $table->foreignId('top_priority_2')->nullable()->constrained('tasks')->nullOnDelete();
    $table->foreignId('top_priority_3')->nullable()->constrained('tasks')->nullOnDelete();
    $table->text('ai_morning_prompt')->nullable();
    $table->text('ai_evening_summary')->nullable();
    $table->unsignedTinyInteger('energy_rating')->nullable();
    $table->unsignedTinyInteger('focus_rating')->nullable();
    $table->unsignedTinyInteger('progress_rating')->nullable();
    $table->text('evening_reflection')->nullable();
    $table->enum('status', ['draft', 'active', 'reviewed'])->default('draft');
    $table->timestamps();

    // Each user can only have one plan per day
    $table->unique(['user_id', 'plan_date']);
    $table->index('user_id');
});
```

---

### `journal_entries` — Updated

```php
Schema::create('journal_entries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->date('entry_date');
    $table->enum('entry_type', ['morning', 'evening', 'weekly', 'freeform'])->default('freeform');
    $table->longText('content');
    $table->unsignedTinyInteger('mood')->nullable();
    $table->json('tags')->nullable();
    $table->text('ai_insights')->nullable();
    $table->timestamps();

    $table->index('user_id');
});
```

---

### `weekly_reviews` — Updated

```php
Schema::create('weekly_reviews', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->date('week_start_date');
    $table->text('wins')->nullable();
    $table->text('friction')->nullable();
    $table->json('outcomes_met')->nullable();
    $table->unsignedTinyInteger('overall_score')->nullable();
    $table->text('ai_analysis')->nullable();
    $table->text('next_week_focus')->nullable();
    $table->timestamps();

    $table->unique(['user_id', 'week_start_date']);
    $table->index('user_id');
});
```

---

### `ai_interactions` — Updated

```php
Schema::create('ai_interactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->enum('interaction_type', [
        'daily-morning', 'daily-evening', 'weekly', 'goal-breakdown', 'freeform',
    ]);
    $table->json('context_json')->nullable();
    $table->text('prompt');
    $table->longText('response')->nullable();
    $table->foreignId('daily_plan_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('goal_id')->nullable()->constrained()->nullOnDelete();
    $table->unsignedSmallInteger('tokens_used')->nullable();
    $table->string('model_used')->nullable();
    $table->timestamps();

    $table->index('user_id');
});
```

---

### New Top-Level Tables — Migration Pattern

**Note:** All new top-level tables follow the same pattern as existing tables:
- `user_id` as the second column after `id`
- `constrained()->cascadeOnDelete()`
- `HasTenant` trait applied to the model

No structural changes to the tenancy architecture are required.

---

## 4. Filament Multi-Tenancy Configuration

Filament v3 has first-class multi-tenancy support. We configure it to use the `User` model as the tenant.

### Panel Configuration

```php
// app/Providers/Filament/AdminPanelProvider.php

use App\Models\User;
use Filament\Panel;

public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('app')                         // /app instead of /admin
        ->tenant(User::class)                 // User IS the tenant
        ->tenantRoutePrefix('')               // No prefix — user is implicit
        ->login()
        ->registration()                      // Enable public registration
        ->passwordReset()
        ->emailVerification()
        ->colors([
            'primary' => Color::hex('#C9A84C'), // Solas Rún gold
            'gray'    => Color::Slate,
        ])
        ->darkMode(false)
        ->navigation(function (NavigationBuilder $builder): NavigationBuilder {
            return $builder->groups([
                NavigationGroup::make('Today')
                    ->items([/* ... */]),
                NavigationGroup::make('Goals & Projects')
                    ->items([/* ... */]),
                NavigationGroup::make('Habits')
                    ->items([/* ... */]),
                NavigationGroup::make('Journal')
                    ->items([/* ... */]),
                NavigationGroup::make('Progress')
                    ->items([/* ... */]),
                NavigationGroup::make('AI Studio')
                    ->items([/* ... */]),
                NavigationGroup::make('Settings')
                    ->items([/* ... */]),
            ]);
        })
        ->resources([
            LifeAreaResource::class,
            GoalResource::class,
            MilestoneResource::class,
            ProjectResource::class,
            TaskResource::class,
            HabitResource::class,
            HabitLogResource::class,
            DailyPlanResource::class,
            TimeBlockResource::class,
            JournalEntryResource::class,
            WeeklyReviewResource::class,
            AiInteractionResource::class,
        ])
        ->widgets([
            DayThemeWidget::class,
            MorningChecklistWidget::class,
            TimeBlockTimelineWidget::class,
            GoalProgressWidget::class,
            HabitRingWidget::class,
            AiIntentionWidget::class,
            StreakHighlightsWidget::class,
        ]);
}
```

---

## 5. Global Scopes

Global scopes automatically filter every query to the authenticated user. This is the core of data isolation.

### Create the TenantScope

```bash
php artisan make:scope TenantScope
```

```php
// app/Models/Scopes/TenantScope.php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check()) {
            $builder->where($model->getTable() . '.user_id', Auth::id());
        }
    }
}
```

### Create the HasTenant Trait

```bash
php artisan make:trait app/Traits/HasTenant
```

```php
// app/Traits/HasTenant.php

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

trait HasTenant
{
    protected static function bootHasTenant(): void
    {
        // Apply global scope — all queries filtered to current user
        static::addGlobalScope(new TenantScope());

        // Automatically assign user_id on creation
        static::creating(function ($model) {
            if (empty($model->user_id) && auth()->check()) {
                $model->user_id = auth()->id();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### Apply the Trait to All Tenant-Scoped Models

Add `use HasTenant;` to each of the following models:

```php
// Apply to: LifeArea, Goal, Project, Task, Habit,
//           DailyPlan, JournalEntry, WeeklyReview, AiInteraction

use App\Traits\HasTenant;

class Goal extends Model
{
    use HasTenant;

    // ... rest of model
}
```

> **Note:** `Milestone`, `HabitLog`, and `TimeBlock` do **not** use `HasTenant` — they are protected through their parent's scope. Queries through the parent relationship are automatically safe.

---

## 6. Policies

Policies add a second layer of protection — even if a direct route is hit, the policy ensures the record belongs to the authenticated user.

```bash
php artisan make:policy GoalPolicy --model=Goal
php artisan make:policy ProjectPolicy --model=Project
php artisan make:policy TaskPolicy --model=Task
php artisan make:policy HabitPolicy --model=Habit
php artisan make:policy DailyPlanPolicy --model=DailyPlan
php artisan make:policy JournalEntryPolicy --model=JournalEntry
php artisan make:policy WeeklyReviewPolicy --model=WeeklyReview
```

All policies follow the same pattern:

```php
// app/Policies/GoalPolicy.php

namespace App\Policies;

use App\Models\Goal;
use App\Models\User;

class GoalPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Filtered by TenantScope
    }

    public function view(User $user, Goal $goal): bool
    {
        return $goal->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Goal $goal): bool
    {
        return $goal->user_id === $user->id;
    }

    public function delete(User $user, Goal $goal): bool
    {
        return $goal->user_id === $user->id;
    }

    public function restore(User $user, Goal $goal): bool
    {
        return $goal->user_id === $user->id;
    }

    public function forceDelete(User $user, Goal $goal): bool
    {
        return $goal->user_id === $user->id;
    }
}
```

Register all policies in `AuthServiceProvider`:

```php
// app/Providers/AuthServiceProvider.php

protected $policies = [
    Goal::class         => GoalPolicy::class,
    Project::class      => ProjectPolicy::class,
    Task::class         => TaskPolicy::class,
    Habit::class        => HabitPolicy::class,
    DailyPlan::class    => DailyPlanPolicy::class,
    JournalEntry::class => JournalEntryPolicy::class,
    WeeklyReview::class => WeeklyReviewPolicy::class,
];
```

---

## 7. Updated Models

### DailyPlan — Updated `today()` and `todayOrCreate()` Helpers

Now scoped to the authenticated user automatically via `HasTenant`, but make the helpers explicit:

```php
use App\Traits\HasTenant;

class DailyPlan extends Model
{
    use HasTenant;

    // ... fillable, casts, relationships unchanged ...

    public static function today(): ?self
    {
        return static::whereDate('plan_date', today())->first();
        // user_id scope applied automatically via HasTenant
    }

    public static function todayOrCreate(): self
    {
        return static::firstOrCreate(
            ['plan_date' => today()->toDateString()],
            [
                'user_id' => auth()->id(),
                'status'  => 'draft',
            ]
        );
    }
}
```

---

## 8. Updated Seeders

The `LifeAreaSeeder` now seeds **per user** — called during onboarding, not at global database seed time.

```php
// app/Services/OnboardingService.php

namespace App\Services;

use App\Models\User;
use App\Models\LifeArea;

class OnboardingService
{
    public function seedDefaultLifeAreas(User $user): void
    {
        $defaults = [
            ['name' => 'Creative',  'icon' => '🎨', 'color_hex' => '#7C3AED', 'sort_order' => 1,
             'description' => 'Writing, music, TV production, and all creative output.'],
            ['name' => 'Business',  'icon' => '💼', 'color_hex' => '#1D4ED8', 'sort_order' => 2,
             'description' => 'Client work, team management, revenue, and growth.'],
            ['name' => 'Health',    'icon' => '💚', 'color_hex' => '#059669', 'sort_order' => 3,
             'description' => 'Physical wellness, mental health, energy, sleep, and nutrition.'],
            ['name' => 'Family',    'icon' => '👨‍👩‍👧', 'color_hex' => '#D97706', 'sort_order' => 4,
             'description' => 'Relationships, presence, shared experiences, and legacy.'],
            ['name' => 'Growth',    'icon' => '📚', 'color_hex' => '#0891B2', 'sort_order' => 5,
             'description' => 'Learning, skills, reading, courses, and spiritual development.'],
            ['name' => 'Finance',   'icon' => '💰', 'color_hex' => '#C9A84C', 'sort_order' => 6,
             'description' => 'Income, expenses, savings, investments, and financial goals.'],
        ];

        foreach ($defaults as $area) {
            LifeArea::create(array_merge($area, ['user_id' => $user->id]));
        }
    }

    public function completeOnboarding(User $user): void
    {
        $user->update(['onboarding_complete' => true]);
    }
}
```

Call `OnboardingService` from a listener on the `Registered` event:

```php
// app/Listeners/SetupNewUser.php

namespace App\Listeners;

use App\Services\OnboardingService;
use Illuminate\Auth\Events\Registered;

class SetupNewUser
{
    public function __construct(protected OnboardingService $onboarding) {}

    public function handle(Registered $event): void
    {
        $this->onboarding->seedDefaultLifeAreas($event->user);
    }
}
```

Register in `EventServiceProvider`:

```php
protected $listen = [
    Registered::class => [
        SetupNewUser::class,
    ],
];
```

---

## 9. Registration & Onboarding Flow

### Registration Route

Filament's built-in registration handles user creation. Customize the registration page to collect timezone:

```php
// app/Filament/Pages/Auth/Register.php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Forms\Components\Select;

class Register extends BaseRegister
{
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                        Select::make('timezone')
                            ->label('Your Timezone')
                            ->options(
                                collect(timezone_identifiers_list())
                                    ->mapWithKeys(fn($tz) => [$tz => $tz])
                                    ->toArray()
                            )
                            ->searchable()
                            ->required()
                            ->default('America/New_York'),
                    ])
                    ->statePath('data'),
            ),
        ];
    }
}
```

### Trial Period

New users start on a 14-day trial. Set this in the `SetupNewUser` listener:

```php
public function handle(Registered $event): void
{
    $event->user->update([
        'subscription_status' => 'trial',
        'trial_ends_at'       => now()->addDays(14),
    ]);

    $this->onboarding->seedDefaultLifeAreas($event->user);
}
```

### Onboarding Wizard (Phase 2)

After registration, redirect users to a simple onboarding wizard that:

1. Confirms their life areas (keep defaults or customize)
2. Sets their first goal in one area
3. Sets their first habit
4. Schedules their first morning session

This is built as a Filament `Page` with a multi-step form in Phase 2 of the build.

---

## 10. Subscription & Access Control

### Middleware: `EnsureActiveSubscription`

```bash
php artisan make:middleware EnsureActiveSubscription
```

```php
// app/Http/Middleware/EnsureActiveSubscription.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureActiveSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && !$user->hasActiveAccess()) {
            return redirect()->route('subscription.expired');
        }

        return $next($request);
    }
}
```

Register in `bootstrap/app.php` (Laravel 11):

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'active.subscription' => EnsureActiveSubscription::class,
    ]);
})
```

Apply to the Filament panel in `AdminPanelProvider`:

```php
->middleware([
    'auth',
    'active.subscription',
])
```

### Subscription Status Reference

| Status | Access | Notes |
|--------|--------|-------|
| `trial` | ✅ Full | 14-day trial from registration |
| `active` | ✅ Full | Paying subscriber |
| `cancelled` | ⚠️ Limited | Read-only until period ends |
| `expired` | ❌ None | Redirect to upgrade page |

### Future: Stripe Integration

Stripe Billing will be integrated in Phase 3. The `stripe_customer_id`, `subscription_status`, and `subscription_ends_at` fields on `users` are already in place. When ready, use:

```bash
composer require laravel/cashier
```

---

## 11. Security Checklist

Before going to production, verify every item below.

### Data Isolation

- [ ] `HasTenant` trait applied to all top-level models
- [ ] `TenantScope` tested — a User B query never returns User A's data
- [ ] All Filament Resources confirmed to only show auth user's records
- [ ] Policies registered and enforced for all resources
- [ ] `cascadeOnDelete()` on all `user_id` foreign keys — deleting a user deletes all their data

### Authentication

- [ ] Email verification enabled (`->emailVerification()` in panel config)
- [ ] Password reset working
- [ ] Rate limiting on login (`throttle:6,1` middleware applied)
- [ ] Registration CSRF protection confirmed

### API / AI

- [ ] AI API key stored in `.env`, never in code or database
- [ ] `ai_interactions` table logs all calls for audit
- [ ] `context_json` does not store passwords, payment info, or other users' data
- [ ] Token limits enforced per user per day (prevent abuse)

### General

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production` in production
- [ ] Database credentials not committed to version control
- [ ] Filament admin panel behind authentication at all times
- [ ] HTTPS enforced in production (`ForceHttps` middleware)

---

*Solas Rún • Version 1.1 • Multi-Tenancy Architecture*
