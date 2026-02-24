# Solas Rún User Guide

> *Start the day with clarity. End the day with progress. Every day is a step toward the life you are building.*

This guide walks you through every feature of Solas Rún, the personal operating system that treats your life with the same rigor as a professional engagement.

---

## Table of Contents

1. [Core Philosophy](#1-core-philosophy)
2. [Life Areas](#2-life-areas)
3. [Goals and Milestones](#3-goals-and-milestones)
4. [Projects](#4-projects)
5. [The Task Tree](#5-the-task-tree)
6. [Specification and Export](#6-specification-and-export)
7. [Daily Planning](#7-daily-planning)
8. [Habits and Streaks](#8-habits-and-streaks)
9. [Journal and Reflection](#9-journal-and-reflection)
10. [Weekly Reviews](#10-weekly-reviews)
11. [Meeting Intelligence](#11-meeting-intelligence)
12. [Deferral Pipeline and Opportunities](#12-deferral-pipeline-and-opportunities)
13. [Budget and Time Tracking](#13-budget-and-time-tracking)
14. [AI Integration Points](#14-ai-integration-points)
15. [Dashboard and Widgets](#15-dashboard-and-widgets)
16. [MCP Server and AI Agents](#16-mcp-server-and-ai-agents)
17. [Daily Workflow SOP](#17-daily-workflow-sop)
18. [Weekly Workflow SOP](#18-weekly-workflow-sop)

---

## 1. Core Philosophy

Solas Rún is built on five principles:

1. **Goals without daily action are wishes.** Daily action without goals is noise.
2. **Clarity comes before planning.** You cannot plan your way to a destination you have not named.
3. **The system adapts to life.** Life does not adapt to the system.
4. **Reflection is not optional.** It is how the system learns and how you grow.
5. **Every area of life deserves intentional attention.** None should be sacrificed permanently.

The relationship between you and Solas Rún follows the **self-as-client model**: your creative projects, health ambitions, and personal goals deserve the same scope definitions, quality standards, and weekly status reviews you give to paying clients.

---

## 2. Life Areas

Everything in Solas Rún is organized under six Life Areas. These are the top-level categories that ensure balanced attention across your whole life.

| Life Area    | Default Color | Scope                                             |
|--------------|---------------|----------------------------------------------------|
| Creative     | Purple        | Writing, music, art, design, production            |
| Business     | Blue          | Client work, revenue, team, contracts              |
| Health       | Green         | Fitness, nutrition, sleep, mental wellness         |
| Family       | Amber         | Relationships, presence, shared experiences        |
| Growth       | Cyan          | Learning, skills, reading, spiritual development   |
| Finance      | Gold          | Income, expenses, savings, investments             |

Life Areas are seeded during installation. You can customize their names, icons, colors, and descriptions through the admin panel under Settings.

Every goal, project, task, and habit belongs to exactly one Life Area. This makes it easy to see at a glance whether you are investing too heavily in one area at the expense of another.

---

## 3. Goals and Milestones

### Goals

Goals are high-level outcomes you want to achieve. Each goal belongs to a Life Area and has:

- **Title** -- What you want to accomplish
- **Why** -- Your motivation. This is surfaced during planning sessions as a reminder
- **Horizon** -- short (90-day), medium (1-year), long (3-year), or lifetime
- **Status** -- active, paused, completed, or abandoned
- **Target date** -- When you aim to complete it
- **Progress percent** -- Automatically calculated from task completion

Goals are the "north star" of the system. Every task, every daily plan, every morning intention traces back to an active goal.

### Milestones

Milestones are mid-level waypoints within a goal. They provide checkpoints for goals longer than 30 days. Each milestone has a title, due date, status (pending/in_progress/completed), and sort order within its goal.

---

## 4. Projects

Projects are concrete initiatives with a defined scope. They may or may not be linked to a goal. Client projects and personal projects live side by side.

Each project has:

- **Name and description**
- **Life Area** assignment
- **Optional goal** link
- **Status**: active, on-hold, complete, archived
- **Client name** (blank for personal projects)
- **Due date** and **color** for visual identification
- **Tech stack** -- Languages, frameworks, versions (for spec export)
- **Architecture notes** -- High-level system design description
- **Export template** -- Custom content to include in exported CLAUDE.md files

Projects contain tasks organized as a hierarchical tree.

---

## 5. The Task Tree

Tasks are the atomic units of work, organized as a tree structure. This is the heart of Solas Rún.

### Tree Structure

- **Root tasks** have no parent (`parent_id = null`)
- **Child tasks** reference their parent via `parent_id`
- **Leaf tasks** (`is_leaf = true`) are the actionable items you actually do
- **Parent tasks** aggregate progress from their children
- **Path** is a materialized string like `"1/4/12/"` for efficient tree queries
- **Depth** tracks nesting level (0 for root tasks)

### Task Fields

Each task has these core fields:

| Field Group         | Fields                                                        |
|---------------------|---------------------------------------------------------------|
| Identity            | title, notes, life_area, project, goal, milestone             |
| Tree                | parent, depth, path, is_leaf, sort_order                      |
| Status              | status (todo/in-progress/done/deferred/blocked), priority     |
| Scheduling          | due_date, scheduled_date, time_estimate_minutes, is_daily_action |
| Specification       | acceptance_criteria, technical_requirements, dependencies_description |
| AI Memory           | plan (implementation plan), context (working context)         |
| Cost                | estimated_cost, actual_cost, billable                         |
| Decomposition       | decomposition_status, two_minute_check                        |
| Quality             | quality_gate_status                                           |
| Deferral            | deferral_reason, deferral_note, revisit_date, deferral_trigger |

### How the Tree Works

1. **Create a root task** for a major deliverable
2. **Decompose** it into child tasks until each leaf passes the "2-minute check" (can you explain exactly what to do?)
3. **Work the leaves** -- complete leaf tasks as actionable items
4. **Quality gates trigger** when all siblings are done, prompting a review before the parent closes
5. **Progress rolls up** automatically through the tree

### Specification Fields

Tasks double as specifications when used for planning:

- **Acceptance Criteria** -- What does "done" look like? Measurable outcomes
- **Technical Requirements** -- Constraints, libraries, patterns, API contracts
- **Dependencies** -- What must be completed first, external blockers

### AI Memory Fields

Tasks persist AI session memory across conversations:

- **Plan** -- Implementation approach, key files, steps, decisions
- **Context** -- Working context, specifications, requirements

These fields are automatically injected into new AI sessions via the SessionStart hook and can be updated via the `update-task-plan` and `update-task-context` MCP tools.

---

## 6. Specification and Export

Solas Rún can export a project's entire task tree as a set of markdown files that bootstrap a new Claude Code project for another team.

### What Gets Generated

```
output_dir/
  CLAUDE.md              -- AI agent instructions (tech stack, architecture, workflow)
  SPECIFICATION.md       -- Project overview, WBS table, dependency map
  specs/
    01-task-title.md     -- Root tasks numbered 01, 02, 03...
    02a-subtask.md       -- Children: 02a, 02b, 02c...
    02a-i-deep.md        -- Grandchildren: 02a-i, 02a-ii...
  CHECKLIST.md           -- All leaf tasks grouped by priority with checkboxes
```

### How to Export

Via the command line:

```bash
php artisan spec:export {project_id} --output=/path/to/output
```

Via the MCP server (for AI agents):

```
Tool: export-project-spec
Arguments: { "project_id": 1 }
```

### Preparing Tasks for Export

For the richest export output, fill in these fields on each task:

1. **Notes** -- The task description and context
2. **Plan** -- Implementation approach
3. **Acceptance Criteria** -- Definition of done
4. **Technical Requirements** -- Tech constraints
5. **Dependencies** -- What must happen first

And on the project:

1. **Tech Stack** -- e.g., "Laravel 12, React 19, PostgreSQL 15"
2. **Architecture Notes** -- High-level system design
3. **Export Template** -- Custom CLAUDE.md content for the implementing team

---

## 7. Daily Planning

Each day in Solas Rún has a **Daily Plan** with morning and evening sessions.

### Morning Session

- **Day Theme** -- One word or phrase that anchors the day
- **Morning Intention** -- A text reflection to set your mindset
- **Top 3 Priorities** -- The three most important tasks for the day
- **AI Morning Prompt** -- AI-generated intention based on your goals and patterns

### Evening Session

- **Energy Rating** (1-5) -- How was your physical energy today?
- **Focus Rating** (1-5) -- How focused were you?
- **Progress Rating** (1-5) -- How much did you accomplish?
- **Evening Reflection** -- Free-form written reflection
- **AI Evening Summary** -- AI-generated review of the day

### Time Blocks

Within a daily plan, you can schedule time blocks:

- **Title** and **block type** (deep-work, admin, meeting, personal, buffer)
- **Start and end times**
- **Linked task or project**
- **Color coding** for visual scanning

The Time Block Timeline widget on the dashboard shows your day's schedule at a glance, highlighting the current block.

---

## 8. Habits and Streaks

### Creating Habits

Habits are recurring behaviors tracked daily. Each habit belongs to a Life Area and has:

- **Frequency**: daily, weekdays, weekly, or custom (specific days)
- **Time of day**: morning, afternoon, evening, or anytime
- **Target days**: JSON array of day numbers for custom schedules
- **Streak tracking**: current streak and personal best

### Tracking Habits

From the Morning Checklist widget on the dashboard, you can check off habits with a single click. The Habit Ring widget shows your daily completion percentage as a circular progress indicator.

### Streaks

The `HabitStreakService` automatically calculates:

- **Current streak** -- Consecutive days completed
- **Best streak** -- All-time personal record
- **Streak recovery** -- How to get back on track after a miss

Streaks of 3+ days appear in the Streak Highlights widget on the dashboard.

---

## 9. Journal and Reflection

Journal entries capture your daily thoughts and reflections. Each entry has:

- **Entry type**: morning, evening, reflection, gratitude, or free
- **Content**: Long-form text (supports markdown)
- **Mood**: 1-5 scale
- **Tags**: Categorization for pattern analysis
- **AI Insights**: AI-generated observations about your patterns

Journaling is the system's feedback loop. The AI uses your journal entries to personalize morning intentions and weekly analyses.

---

## 10. Weekly Reviews

Every week gets a structured review with:

- **Wins** -- What went well this week
- **Friction** -- What blocked you or drained you
- **Outcomes Met** -- Per-life-area scores (JSON)
- **Overall Score** -- 1-5 holistic rating
- **AI Analysis** -- AI-generated pattern insights
- **Next Week Focus** -- Intentions for the upcoming week

The weekly review closes the loop on your planning rhythm and feeds into the AI's understanding of your patterns.

---

## 11. Meeting Intelligence

Solas Rún treats every meeting as a first-class intelligence event.

### Client Meetings

Track meetings with:

- **Meeting type**: kickoff, status, review, planning, retrospective, ad hoc
- **Client type**: new, existing, returning
- **Summary, decisions, and action items**
- **Transcript** (synced via Granola MCP)
- **Transcription status**: pending, processing, completed, failed

### Meeting Agendas

Prepare structured agendas before meetings with:

- **Title, purpose, and client context**
- **Individual agenda items** (discussion, decision, information, action)
- **Linked project** for context

### Meeting Intelligence Extraction

After a meeting, the system extracts:

- **Done Items** -- Work confirmed as completed, with client quotes and value delivered
- **Scope Items** -- In-scope, out-of-scope, risks, and assumptions
- **Resource Signals** -- Budget, time, personnel, and technology constraints mentioned

### Granola MCP Integration

Meeting transcripts are synced on demand from Granola via its MCP interface. No webhooks or transcript storage required.

---

## 12. Deferral Pipeline and Opportunities

When work is deferred, it does not disappear. It enters a structured pipeline.

### Deferred Items

Every deferred task becomes a tracked asset with:

- **Why it was deferred** (budget, timing, resource, client decision, strategic)
- **Client context** and direct quotes
- **Estimated value** (commercial opportunity)
- **Revisit date** for follow-up
- **Status**: active, revisit_due, converted, archived, lost

### Deferral Reviews

Periodic reviews cycle through active deferred items:

- **Outcome**: keep watching, revisit soon, convert, archive, lost
- **Context update**: What has changed since deferral?
- **Next revisit date**: When to check again

### Opportunity Pipeline

Deferred items that mature into real opportunities enter the pipeline:

- **Stages**: lead, qualified, proposal, negotiation, won, lost
- **Estimated and actual value**
- **Expected close date** and next action

The Opportunity Pipeline widget on the dashboard surfaces items that need attention.

---

## 13. Budget and Time Tracking

### Project Budgets

Track financial health for each project:

- **Budget type**: fixed, hourly, retainer, milestone
- **Total budget** and **spent amount**
- **Hourly rate** for hourly projects
- **Budget status**: on-track, warning, over-budget

### Time Entries

Log time against tasks and projects:

- **Hours worked** and **logged date**
- **Description** of work performed
- **Billable flag** for client work

---

## 14. AI Integration Points

Solas Rún provides eight dedicated AI integration points:

| Integration            | Purpose                                                |
|------------------------|--------------------------------------------------------|
| Morning Intention      | Set the tone and focus for the day                     |
| Goal Breakdown         | Decompose goals into actionable task trees             |
| Daily Plan Builder     | Generate a structured plan from tasks and habits       |
| Evening Summary        | Reflect on what was accomplished                       |
| Weekly Analysis        | Identify patterns and adjust strategy                  |
| Freeform Studio        | Open-ended brainstorming and planning                  |
| Goal Brainstorming     | Explore new goals and evaluate feasibility             |
| Resource Readiness     | Assess readiness to start a project                    |

Every AI interaction is logged in the `ai_interactions` table for transparency and pattern analysis.

---

## 15. Dashboard and Widgets

The Daily Command Center presents nine widgets:

| # | Widget              | Span    | Purpose                                        |
|---|---------------------|---------|------------------------------------------------|
| 1 | Day Theme           | Full    | Date, theme, yesterday's energy/focus/progress |
| 2 | AI Intention        | Full    | Morning intention with generate button         |
| 3 | Morning Checklist   | 2 cols  | Top 3 priorities + habit completion            |
| 4 | Time Block Timeline | 1 col   | Visual schedule with "NOW" indicator           |
| 5 | Goal Progress       | 2 cols  | Active goals with progress bars                |
| 6 | Habit Ring          | 1 col   | Circular completion percentage + top streaks   |
| 7 | Streak Highlights   | 1 col   | Active habit streaks + recent milestones       |
| 8 | Opportunity Pipeline| 1 col   | Deferred items needing attention               |
| 9 | Done/Delivered      | 1 col   | Recent outcomes and monthly value delivered    |

Widgets auto-refresh and communicate via Livewire events (e.g., completing a habit updates both the Checklist and Ring widgets).

---

## 16. MCP Server and AI Agents

The `solas-run` MCP server runs via STDIO alongside the `laravel-boost` server. It provides 53 tools and 25 resource templates.

### Available Tool Patterns

- `inspect-{slug}` -- Schema introspection (fillable, casts, relationships, filters)
- `list-{slug}` -- Query with filters, date ranges, ilike search, pagination
- `update-task-plan` -- Save implementation plan to a task
- `update-task-context` -- Save working context to a task
- `export-project-spec` -- Export project task tree as markdown

### Resource Templates

- `{slug}://solas-run/{id}` -- Fetch any record by ID with BelongsTo relations loaded

### Claude Code Integration

The system includes three hooks for Claude Code sessions:

1. **SessionStart** -- Injects active task context from the database
2. **UserPromptSubmit** -- Suggests relevant skills based on prompt keywords
3. **Stop** -- Reminds to save task plan and context via MCP tools

---

## 17. Daily Workflow SOP

### Morning (15-20 minutes)

1. Open the Daily Plan page. Review yesterday's completion status
2. Read your AI Morning Intention. Let it set the tone
3. Set your Day Theme -- one word or short phrase
4. Confirm your Top 3 priorities. These must move a goal forward
5. Review your time blocks. Protect at least one deep work block
6. Check today's habits from the Morning Checklist widget
7. **Close the app. Work.**

### During the Day

- Log task completions as they happen
- Mark habit completions from the dashboard
- If something urgent displaces a priority, note why
- Use the AI Studio for brainstorming or planning decisions

### Evening (10-15 minutes)

1. Review task completions. Mark remaining as done, deferred, or dropped
2. Confirm habit log -- any missed habits to note?
3. Rate the day: Energy, Focus, Progress (each 1-5)
4. Write your reflection -- honesty matters more than positivity
5. Trigger AI evening summary and read it
6. Glance at tomorrow. Make one adjustment if needed. Then close.

---

## 18. Weekly Workflow SOP

### Monday Planning (30-45 minutes)

1. Open the Weekly Review from last week. Read your own words
2. Review the Goal Progress dashboard -- where are you ahead, behind, stalled?
3. Set this week's intended outcomes -- one per life area maximum
4. Schedule your week -- time blocks first, then meetings, then fill gaps
5. Reset or add habits for the week
6. Request the AI Weekly Briefing and review its suggestions

### Friday/Sunday Review (20-30 minutes)

1. Score the week honestly across each life area (1-5)
2. Log your wins -- at least three, no matter how small
3. Log friction -- what blocked, drained, or did not work
4. Write the weekly reflection journal entry
5. Trigger AI Weekly Analysis and save it with the review
6. Carry forward any unfinished outcomes or demote them
7. Check the opportunity pipeline for items due for follow-up
