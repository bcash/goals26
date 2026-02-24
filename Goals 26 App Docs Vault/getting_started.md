# Getting Started with Solas Rún

## What is Solas Rún?

Solas Rún is a **personal operating system** built on Laravel 12 and Filament v3. It brings the same discipline, structure, and accountability you apply to client work into your personal goals, habits, and daily routines.

The name comes from Irish: **Solas** (Light) and **Rún** (Secret/Mystery) -- illuminating the hidden patterns of how you spend your time and energy so you can direct them with intention.

Solas Rún unifies goal tracking, project management, task decomposition, habit building, journaling, meeting intelligence, AI coaching, and spec export into a single, self-hosted application. It is designed around the **self-as-client model**: you treat your personal goals with the same rigor, planning, and follow-through that you would give to a paying client.

---

## Prerequisites

| Requirement          | Minimum Version | Notes                                       |
|----------------------|-----------------|----------------------------------------------|
| PHP                  | 8.2+            | With required Laravel extensions enabled      |
| PostgreSQL           | 15+             | Primary database (port 5468 by default)       |
| Node.js              | 18+             | For frontend asset compilation                |
| Composer             | 2.x             | PHP dependency management                     |
| Laravel Herd or Valet| Latest          | Local development server                      |

---

## Installation

### 1. Clone the Repository

```bash
git clone git@github.com:your-org/solas-run.git goals26
cd goals26
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Configure Environment

```bash
cp .env.example .env
```

Open `.env` and configure your database connection. Solas Rún uses PostgreSQL:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5468
DB_DATABASE=solas_run
DB_USERNAME=postgres
DB_PASSWORD=
```

Adjust `DB_PORT` and `DB_PASSWORD` to match your local PostgreSQL setup.

### 4. Generate Application Key

```bash
php artisan key:generate
```

### 5. Install and Build Frontend Assets

```bash
npm install && npm run build
```

### 6. Run Migrations and Seed Data

```bash
php artisan migrate --seed
```

This creates the database schema (33 migrations, 25 tables), populates the six Life Areas, and provisions a default admin user with demo data including sample projects, tasks (with hierarchical tree), habits, goals, and meetings.

### 7. Access the Application

Open your browser and navigate to:

```
http://goals26.test/admin
```

If you are using Laravel Herd, the `.test` domain is configured automatically. If you are using Valet, run `valet link goals26` from the project root.

### Default Credentials

| Field    | Value                  |
|----------|------------------------|
| Email    | admin@solasrun.com     |
| Password | password               |

Change your password immediately after first login.

---

## Architecture at a Glance

| Component      | Technology                                       |
|----------------|--------------------------------------------------|
| Framework      | Laravel 12                                        |
| Admin Panel    | Filament v3                                       |
| Database       | PostgreSQL 15+ (with TOAST for large text fields) |
| AI Tooling     | Laravel Boost v2.2 + MCP Servers                  |
| MCP Servers    | `laravel-boost` (dev tools) + `solas-run` (data)  |
| Frontend       | Tailwind CSS 4 + Livewire                         |
| Models         | 25 Eloquent models across 6 domains               |
| Services       | 16 dedicated service classes                       |
| Widgets        | 9 dashboard widgets                                |
| Filament Resources | 19 admin panel resources                       |

### 25 Models, 6 Domains

| Domain             | Models                                                                |
|--------------------|-----------------------------------------------------------------------|
| Life Foundation    | User, LifeArea                                                        |
| Goals & Projects   | Goal, Milestone, Project, Task, TaskQualityGate                       |
| Daily Rhythm       | DailyPlan, TimeBlock, Habit, HabitLog                                 |
| Reflection         | JournalEntry, WeeklyReview                                           |
| Meeting Intelligence | ClientMeeting, MeetingAgenda, AgendaItem, MeetingDoneItem, MeetingScopeItem, MeetingResourceSignal |
| Pipeline & Tracking | DeferredItem, DeferralReview, OpportunityPipeline, ProjectBudget, TimeEntry, AiInteraction |

### Multi-Tenancy

20 of 25 models use the `HasTenant` trait with a `TenantScope` that automatically filters all queries to the authenticated user. Data isolation is built in from day one.

### Six Life Areas

All goals, projects, and habits are organized under six Life Areas: **Creative**, **Business**, **Health**, **Family**, **Growth**, and **Finance**. These ensure balanced attention across every dimension of your life.

### Hierarchical Task Tree

Tasks form a tree via `parent_id` with materialized paths (`path`, `depth`, `is_leaf`). Leaf tasks are the actionable work items. Parent tasks aggregate progress. Quality gates trigger automatically when all sibling leaves are complete.

### MCP Server (53 Tools)

The `solas-run` MCP server gives AI agents full read/write access to all 25 models: inspect schemas, query with filters, fetch single records, persist task plans and context, and export project specs.

### Spec Export

Export a project's entire task tree as markdown specification files for bootstrapping a new Claude Code project:

```bash
php artisan spec:export {project_id}
```

---

## Navigation Structure

| Section              | What It Contains                                            |
|----------------------|-------------------------------------------------------------|
| **Today**            | Dashboard (9 widgets), daily plan, time blocks              |
| **Goals & Projects** | Life Areas, goals, milestones, projects, tasks              |
| **Habits**           | Habit definitions, tracking, streaks, habit logs            |
| **Journal**          | Journal entries, reflections, mood tracking                 |
| **Progress**         | Weekly reviews, streak highlights, trend analysis           |
| **AI Studio**        | AI interactions: morning intention, goal breakdown, freeform|
| **Settings**         | Profile, preferences, integrations                          |

---

## Next Steps

1. **First login** -- Open `goals26.test/admin`, log in, and explore the demo data
2. **Read the User Guide** -- See `_docs/user_guide.md` for a complete feature walkthrough
3. **Browse the User Manual** -- See `_docs/user_manual/` for detailed reference chapters
4. **Set up your Life Areas** -- Customize the six default areas to match your life
5. **Create your first goal** -- Under any Life Area, create a goal with a "why"
6. **Decompose into tasks** -- Break the goal into a project with a hierarchical task tree
7. **Run a morning session** -- Use the Dashboard to set your day theme and top priorities
8. **Build habits** -- Add daily habits and track them from the Morning Checklist widget
