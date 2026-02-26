# Solas Rún — Personal Operating System

A full-featured personal operating system for managing goals, projects, tasks, habits, daily plans, client meetings, opportunity pipelines, and budgets across six life areas. Built on Laravel 12, Filament v3, and PostgreSQL.

The name *Solas Rún* (Irish: "light of mystery/secret") reflects the project's purpose: bringing clarity and structure to the complex, interconnected dimensions of a productive life.

## Tech Stack

| Layer       | Technology                          |
|-------------|-------------------------------------|
| Framework   | Laravel 12                          |
| Admin Panel | Filament v3                         |
| Database    | PostgreSQL 15+                      |
| Frontend    | Tailwind CSS v4, Vite 7, Livewire 3 |
| Testing     | PHPUnit 11                          |
| AI Tooling  | MCP server (Model Context Protocol) |
| Dev Server  | Laravel Herd (`goals26.test`)       |

## Core Concepts

### Six Life Areas

Every goal, project, task, and habit is organised under one of six life areas — **Creative**, **Business**, **Health**, **Family**, **Growth**, and **Finance** — each with its own colour and icon. This ensures balanced attention across all dimensions of life rather than letting one area dominate.

### Hierarchical Task Tree

Tasks form a tree structure via `parent_id` with materialised paths for efficient querying. Leaf tasks are the actionable units; non-leaf tasks are containers that roll up progress from their children. When all sibling leaves complete, a **quality gate** fires automatically — an AI-generated review checklist that asks whether the parent task's intent was truly met before marking it done.

### Daily Command Center

The dashboard is the operational hub — a single-screen view of today's theme, top 3 priorities, habit checklist, time blocks, goal progress, active streaks, AI-generated morning intentions, pipeline status, and recent deliverables. It's designed around the daily planning workflow: set intentions in the morning, execute through the day, reflect in the evening.

### Deferral-to-Opportunity Pipeline

Out-of-scope items and deferred tasks don't just disappear. They flow into a **Someday/Maybe** list with scheduled revisit dates. Items with commercial value can be promoted into the **Opportunity Pipeline**, tracked through stages (Lead → Qualified → Proposal → Negotiation → Won/Lost), and eventually converted into full projects with budgets.

### Task Memory

Each task has `plan` and `context` text fields that persist AI session memory — implementation steps, key decisions, architecture notes, relevant files. This means an AI assistant can pick up exactly where it left off, even across separate sessions.

## Architecture

### 26 Models

| Domain | Models |
|--------|--------|
| **Foundation** | `User`, `LifeArea` |
| **Goals & Projects** | `Goal`, `Milestone`, `Project`, `ProjectBudget` |
| **Task Tree** | `Task`, `TaskQualityGate` |
| **Habits** | `Habit`, `HabitLog` |
| **Daily Planning** | `DailyPlan`, `TimeBlock` |
| **Reflection** | `JournalEntry`, `WeeklyReview` |
| **Client Meetings** | `ClientMeeting`, `MeetingScopeItem`, `MeetingDoneItem`, `MeetingResourceSignal`, `MeetingAgenda`, `AgendaItem` |
| **Deferrals & Pipeline** | `DeferredItem`, `DeferralReview`, `OpportunityPipeline` |
| **Time & Cost** | `TimeEntry`, `CostEntry` |
| **AI** | `AiInteraction` |

### Service Layer (20 services)

All business logic lives in `app/Services/`. Key services:

- **TaskTreeService** — Tree CRUD, completion propagation, quality gate triggering
- **DailyPlanService** — AI-assisted plan generation, priority management
- **QualityGateService** — Automatic review checkpoints with AI-generated checklists
- **DecompositionInterviewService** — AI-guided task breakdown into subtasks
- **DeferralService** — Defer tasks, capture ideas, manage revisit cycles
- **OpportunityPipelineService** — Pipeline stages, weighted values, project conversion
- **MeetingIntelligenceService** — Extract structured data from meeting transcripts
- **HabitStreakService** — Streak calculation across daily/weekly/custom frequencies
- **GoalProgressService** — Progress aggregation, on-track/at-risk detection
- **BudgetService** — Budget tracking, burn rates, threshold alerts
- **AgendaService** — AI-suggested meeting agenda generation
- **AiService** — Central AI orchestration (morning intentions, goal breakdowns, meeting analysis)
- **GranolaSyncService** — Sync meeting transcripts from Granola
- **SpecExportService** — Export project task trees as markdown specs
- **VpoService** — Virtual Practice Office integration (accounts, invoices, tickets)

### Filament Admin Panel

20 Filament resources provide full CRUD for every model, plus:

- **Dashboard** — 10 custom widgets arranged in a responsive grid
- **TaskTree** — Interactive hierarchical task visualisation with expand/collapse, inline completion, and child creation
- **VpoAccounts** — External account browser (conditional on VPO integration)

### MCP Server

The `solas-run` MCP server (`app/Mcp/`) exposes the entire data model to AI assistants:

- **52 tools**: `inspect-{model}` (schema introspection) + `list-{model}` (filtered queries) for all 26 models, plus `update-task-plan`, `update-task-context`, and `export-project-spec`
- **26 resources**: Single-record fetch via URI templates (`{model}://solas-run/{id}`)
- **Data-driven architecture**: `ModelRegistry` centralises filter definitions, searchable fields, date columns, and eager-loaded relationships. `ModelResolver` builds queries and introspects schemas.

### Multi-Tenancy

20 of 26 models use the `HasTenant` trait with a `TenantScope` global scope that filters all queries by `user_id`. The MCP server bypasses tenant scopes via `withoutGlobalScopes()` since it runs locally without authentication.

## Dashboard Widgets

| Widget | Description |
|--------|-------------|
| **Day Theme** | Today's theme with yesterday's energy/focus/progress ratings |
| **Morning Checklist** | Top 3 priorities + habit completion toggles |
| **Time Block Timeline** | Today's scheduled blocks |
| **Goal Progress** | Active goals grouped by life area with progress bars |
| **Habit Ring** | SVG circular progress visualisation + top streaks |
| **AI Intention** | AI-generated morning intention with regenerate button |
| **Streak Highlights** | Active streaks (3+ days) and recent milestones |
| **Opportunity Pipeline** | Pipeline value by stage, overdue actions |
| **Done & Delivered** | Recent client deliverables and value metrics |
| **VPO Status** | External account connection health |

## Database Conventions

- **PostgreSQL**: `ilike` for case-insensitive search, `jsonb` for array columns
- **Money**: Stored as `decimal(10,2)` (dollar amounts) or integer cents with `MoneyCast`
- **Dates**: Carbon via `date`/`datetime` casts
- **Enums**: Stored as varchar strings, not PHP `BackedEnum`
- **Trees**: Materialised `path` column (e.g., `"1/4/12/"`) with `depth` and `is_leaf`

## Getting Started

### Prerequisites

- PHP 8.2+
- PostgreSQL 15+
- Node.js & npm
- [Laravel Herd](https://herd.laravel.com/) (recommended) or any local server
- Composer

### Installation

```bash
git clone <repo-url> goals26
cd goals26
composer setup
```

The `composer setup` script runs `composer install`, copies `.env.example`, generates the app key, runs migrations, installs npm dependencies, and builds frontend assets.

### Configuration

Copy `.env.example` to `.env` and configure:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5468
DB_DATABASE=solas_run
DB_USERNAME=postgres
DB_PASSWORD=
```

### Database Setup

```bash
php artisan migrate
php artisan db:seed --class=DemoSeeder   # optional: populate with demo data
```

### Development

```bash
composer run dev
```

This starts the Laravel server, queue worker, log tail (Pail), and Vite dev server concurrently.

### Running Tests

```bash
php artisan test --compact                          # all tests
php artisan test --compact tests/Feature/CostEntryTest.php  # specific file
php artisan test --compact --filter=testName         # specific test
```

### Code Style

```bash
vendor/bin/pint --dirty --format agent    # fix modified files
```

## Project Structure

```
app/
├── Casts/              # MoneyCast
├── Filament/
│   ├── Pages/          # Dashboard, TaskTree, VpoAccounts
│   ├── Resources/      # 20 Filament resources with relation managers
│   └── Widgets/        # 11 dashboard widgets
├── Mcp/
│   ├── Servers/        # SolasRunServer
│   ├── Tools/          # InspectModel, ListModels, UpdateTaskPlan, etc.
│   ├── Resources/      # ModelRecord
│   ├── ModelRegistry.php
│   └── ModelResolver.php
├── Models/             # 26 Eloquent models
├── Services/           # 20 service classes
└── Traits/             # HasTenant, HasVpoAccount

database/
├── factories/          # Model factories
├── migrations/         # 33 migrations
└── seeders/            # DemoSeeder, LifeAreaSeeder

resources/
└── views/
    └── filament/
        ├── pages/      # Custom page Blade templates
        ├── widgets/    # Custom widget Blade templates
        └── components/ # task-tree-node

tests/
├── Feature/            # Integration tests
└── Unit/               # Unit tests
```

## External Integrations

- **Granola** — Meeting transcript sync via MCP client (`GranolaSyncService` / `GranolaMcpClient`)
- **VPO (Virtual Practice Office)** — Account, server, domain, website, task, and invoice data via REST API (`VpoService` / `VpoApiClient`), authenticated with Sanctum bearer tokens, configured in `config/vpo.php`
- **AI** — Central orchestration via `AiService` with interaction logging to `AiInteraction` model. Currently uses simulated responses; designed for OpenAI/Anthropic API integration.

## Key Workflows

### Morning Routine
1. Dashboard loads today's plan (or auto-creates one)
2. AI generates a morning intention based on active goals and priorities
3. Top 3 priorities are selected from actionable leaf tasks
4. Habits appear as a checklist with streak context

### Task Decomposition
1. Select a task that needs breakdown
2. AI conducts a decomposition interview — asking clarifying questions
3. Suggested subtasks are created as children in the tree
4. Each new leaf gets a two-minute check flag for actionability

### Meeting Intelligence
1. Meeting transcript arrives (manually or via Granola sync)
2. `MeetingIntelligenceService` extracts: done items, scope items, deferred items, resource signals, and action items
3. Action items become tasks; out-of-scope items become deferred items
4. Deferred items with commercial value enter the opportunity pipeline

### Quality Gates
1. All sibling leaf tasks under a parent are marked complete
2. A quality gate triggers automatically
3. AI generates a context-aware review checklist
4. Reviewer approves or rejects — rejection reopens child tasks

### Weekly Review
1. `WeeklyReview` captures wins, friction points, and outcomes
2. AI generates analysis and focus areas for the coming week
3. Deferred items due for review are surfaced for decision (keep/reschedule/promote/archive)
