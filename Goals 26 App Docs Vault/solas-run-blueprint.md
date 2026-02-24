# SOLAS RÚN
### *The Light of Purpose*
**Personal Operating System — System Design Blueprint v1.2**

---

> *Start the day with clarity. End the day with progress. Every day is a step toward the life you are building.*

---

## Table of Contents

1. [Vision & Philosophy](#i-vision--philosophy)
2. [Planning Rhythms](#ii-planning-rhythms)
3. [Data Model Overview](#iii-data-model-overview)
4. [Filament UI Structure](#iv-filament-ui-structure)
5. [AI Features](#v-ai-features)
6. [Daily Workflow SOP](#vi-daily-workflow--standard-operating-procedure)
7. [Build Phases](#vii-build-phases)
8. [Closing Principle](#viii-closing-principle)

---

## I. Vision & Philosophy

### What is Solas Rún?

Solas Rún (*"the light of purpose"* — from Irish Gaelic) is a personal operating system built on Laravel and Filament PHP. It is not a task manager. It is not a calendar. It is the system that holds your long-term goals in view every single day and ensures that the way you spend your time reflects what matters most to you.

### Core Philosophy

| # | Principle |
|---|-----------|
| 1 | Goals without daily action are wishes. Daily action without goals is noise. |
| 2 | Clarity must come before planning. You cannot plan your way to a destination you haven't named. |
| 3 | The system adapts to life. Life does not adapt to the system. |
| 4 | Reflection is not optional — it is how the system learns and how you grow. |
| 5 | Every area of life deserves intentional attention — none should be sacrificed for another permanently. |

The client relationship in Solas Rún is not limited to external paying customers. **You are your own most important client.** Your creative projects, life goals, health ambitions, and personal development follow the same discipline as any professional engagement: they have a scope, a budget of resources, deliverables, a quality standard, and things that are in-scope, deferred, or not worth pursuing. When you sit down to plan your week, that is a meeting with yourself. Treat it with the same rigor.

### The Six Life Areas

Solas Rún organizes all goals, projects, habits, and tasks within six life areas. These are not departments — they are the interconnected dimensions of a whole life.

| Area | Updated Scope |
|------|--------------|
| 🎨 Creative | Writing (sci-fi, lyrics, family history), music composition, TV production. You are the client, producer, and creative director. |
| 💼 Business | Client work, webmaster team, contracts, revenue, growth. External clients and internal team goals. |
| 💚 Health | Physical wellness, mental health, energy, sleep, nutrition, movement. Your body is a project with a budget, a roadmap, and a quality standard. |
| 👨‍👩‍👧 Family | Relationships, presence, shared experiences, legacy. These have timelines, deliverables, and things that can be deferred too long. |
| 📚 Growth | Learning, skills, reading, courses, curiosity, spiritual development. Resources needed: time, money, energy, and prerequisite capabilities. |
| 💰 Finance | Income, expenses, savings, investments, financial goals. The resource foundation that enables or constrains every other area. |

---

## II. Planning Rhythms

### The Daily Rhythm

Every day in Solas Rún has a **morning session** and an **evening session**. These are short, focused, and non-negotiable.

#### Morning Session *(15–20 min)*

- Review your Top 3 priorities for the day — pulled from active goals
- Confirm your time blocks for deep work, meetings, and personal time
- Check in on active habits — which ones are scheduled today?
- Read your AI-generated daily intention — a focused prompt to orient your mindset
- Set your **Day Theme** — one word or phrase that anchors the day

#### Evening Session *(10–15 min)*

- Mark tasks complete, deferred, or dropped
- Rate the day: Energy (1–5), Focus (1–5), Progress (1–5)
- Write a brief reflection — what went well, what was hard, what you learned
- Review tomorrow's plan — adjust if needed
- AI end-of-day summary — patterns, encouragement, suggestions
- Review any done items logged from today's work — note outcomes and impact
- Flag any tasks completed today that produced notable results worth capturing

---

### The Weekly Rhythm

Every **Monday** (or Sunday evening), a Weekly Planning Session sets the tone for the week. Every **Friday** (or Sunday), a Weekly Review closes the loop.

#### Monday Planning Session *(30–45 min)*

- Review progress on all active goals across the six life areas
- Identify the most important outcomes for the week — one per life area maximum
- Schedule time blocks for the week — protect deep work time first
- Review and reset habits — are any habits paused, resumed, or new?
- AI weekly briefing — a suggested focus based on recent patterns and goal progress

#### Friday / Sunday Review Session *(20–30 min)*

- Score the week: did you hit your intended outcomes?
- Celebrate wins — log them in the Progress Log
- Identify friction — what blocked you? Update goals or systems accordingly
- Write a weekly reflection journal entry
- AI weekly analysis — streaks, trend insights, patterns of energy and output
- Review Someday/Maybe list — are any personal goals now resourced and ready to activate?
- Check opportunity pipeline — any deferred client or personal items with next actions due?

---

## III. Data Model Overview

The following entities form the backbone of the Solas Rún Laravel application. Each will become a Filament Resource.

### 1. `life_areas`

| Field | Type | Notes |
|-------|------|-------|
| `id` | bigIncrements | |
| `name` | string | e.g. "Creative", "Business" |
| `icon` | string | emoji or Heroicon name |
| `color_hex` | string | UI color |
| `description` | text | nullable |
| `sort_order` | integer | |

*Seeded with the six core areas. User can customize labels and colors.*

---

### 2. `goals`

| Field | Type | Notes |
|-------|------|-------|
| `id` | bigIncrements | |
| `life_area_id` | foreignId | |
| `title` | string | |
| `description` | text | nullable |
| `why` | text | The motivation — shown during planning |
| `horizon` | enum | `90-day`, `1-year`, `3-year`, `lifetime` |
| `status` | enum | `active`, `paused`, `achieved`, `abandoned` |
| `target_date` | date | nullable |
| `progress_percent` | integer | 0–100, auto-calculated |
| `created_at` | timestamp | |

*Goals must have a `why`. This is surfaced during planning sessions.*

---

### 3. `milestones`

| Field | Type | Notes |
|-------|------|-------|
| `id` | bigIncrements | |
| `goal_id` | foreignId | |
| `title` | string | |
| `due_date` | date | nullable |
| `status` | enum | `pending`, `complete` |
| `sort_order` | integer | |

*Mid-level waypoints between a long-term goal and a daily action. Optional but recommended for goals longer than 30 days.*

---

### 4. `projects`

| Field | Type | Notes |
|-------|------|-------|
| `id` | bigIncrements | |
| `life_area_id` | foreignId | |
| `goal_id` | foreignId | nullable |
| `name` | string | |
| `description` | text | nullable |
| `status` | enum | `active`, `on-hold`, `complete`, `archived` |
| `client_name` | string | nullable |
| `due_date` | date | nullable |
| `color_hex` | string | nullable |

*Projects may or may not be linked to a goal. Client projects live here. Each project has Tasks.*

---

### 5. `tasks`

| Field | Type | Notes |
|-------|------|-------|
| `id` | bigIncrements | |
| `project_id` | foreignId | nullable |
| `goal_id` | foreignId | nullable |
| `milestone_id` | foreignId | nullable |
| `life_area_id` | foreignId | |
| `title` | string | |
| `notes` | text | nullable |
| `status` | enum | `todo`, `in-progress`, `done`, `deferred` |
| `priority` | enum | `low`, `medium`, `high`, `critical` |
| `due_date` | date | nullable |
| `scheduled_date` | date | nullable |
| `time_estimate_minutes` | integer | nullable |
| `is_daily_action` | boolean | default false |

*Tasks are the atomic unit of work. Daily Actions are tasks flagged `is_daily_action` and scheduled for today.*

---

### 6. `habits`

| Field | Type | Notes |
|-------|------|-------|
| `id` | bigIncrements | |
| `life_area_id` | foreignId | |
| `title` | string | |
| `description` | text | nullable |
| `frequency` | enum | `daily`, `weekdays`, `weekly`, `custom` |
| `target_days` | json | Array of day numbers (0=Sun … 6=Sat) |
| `time_of_day` | enum | `morning`, `afternoon`, `evening`, `anytime` |
| `status` | enum | `active`, `paused` |
| `streak_current` | integer | default 0 |
| `streak_best` | integer | default 0 |
| `started_at` | date | |

---

### 7. `habit_logs`

| Field | Type | Notes |
|-------|------|-------|
| `id` | bigIncrements | |
| `habit_id` | foreignId | |
| `logged_date` | date | |
| `status` | enum | `completed`, `skipped`, `missed` |
| `note` | string | nullable |

*One record per habit per scheduled day. Streaks calculated from this table.*

---

### 8. `daily_plans`

| Field | Type | Notes |
|-------|------|-------|
| `id` | bigIncrements | |
| `plan_date` | date | unique |
| `day_theme` | string | nullable |
| `morning_intention` | text | nullable |
| `top_priority_1` | foreignId | task_id, nullable |
| `top_priority_2` | foreignId | task_id, nullable |
| `top_priority_3` | foreignId | task_id, nullable |
| `ai_morning_prompt` | text | nullable |
| `ai_evening_summary` | text | nullable |
| `energy_rating` | tinyInteger | 1–5, nullable |
| `focus_rating` | tinyInteger | 1–5, nullable |
| `progress_rating` | tinyInteger | 1–5, nullable |
| `evening_reflection` | text | nullable |
| `status` | enum | `draft`, `active`, `reviewed` |

*One record per day. The central hub for morning and evening sessions.*

---

### 9. `time_blocks`

| Field | Type | Notes |
|-------|------|-------|
| `id` | bigIncrements | |
| `daily_plan_id` | foreignId | |
| `title` | string | |
| `block_type` | enum | `deep-work`, `admin`, `meeting`, `personal`, `buffer` |
| `start_time` | time | |
| `end_time` | time | |
| `task_id` | foreignId | nullable |
| `project_id` | foreignId | nullable |
| `notes` | text | nullable |
| `color_hex` | string | nullable |

---

### 10. `journal_entries`

| Field | Type | Notes |
|-------|------|-------|
| `id` | bigIncrements | |
| `entry_date` | date | |
| `entry_type` | enum | `morning`, `evening`, `weekly`, `freeform` |
| `content` | longText | |
| `mood` | tinyInteger | 1–5, nullable |
| `tags` | json | nullable |
| `ai_insights` | text | nullable |
| `created_at` | timestamp | |

---

### 11. `weekly_reviews`

| Field | Type | Notes |
|-------|------|-------|
| `id` | bigIncrements | |
| `week_start_date` | date | |
| `wins` | text | nullable |
| `friction` | text | nullable |
| `outcomes_met` | json | Per life area scores |
| `overall_score` | tinyInteger | 1–5 |
| `ai_analysis` | text | nullable |
| `next_week_focus` | text | nullable |
| `created_at` | timestamp | |

---

### 12. `ai_interactions`

| Field | Type | Notes |
|-------|------|-------|
| `id` | bigIncrements | |
| `interaction_type` | enum | `daily-morning`, `daily-evening`, `weekly`, `goal-breakdown`, `freeform` |
| `context_json` | json | Snapshot of relevant data sent to AI |
| `prompt` | text | |
| `response` | text | |
| `daily_plan_id` | foreignId | nullable |
| `goal_id` | foreignId | nullable |
| `created_at` | timestamp | |

*Every AI call is logged for transparency and future analysis.*

---

### 13. `meeting_done_items`

| Field | Type | Notes |
|-------|------|-------|
| Entity | Purpose |
| `meeting_done_items` | Completed work confirmed in meetings — outcomes, client reactions, value delivered |

---

### 14. `meeting_resource_signals`

| Entity | Purpose |
| `meeting_resource_signals` | Resource constraints mentioned in meetings — budget, time, capability, technology |

---

### 15. `meeting_agendas`

| Entity | Purpose |
| `meeting_agendas` | Structured agendas for upcoming meetings |

---

### 16. `agenda_items`

| Entity | Purpose |
| `agenda_items` | Individual topics, follow-ups, and deferred reviews on an agenda |

---

**Updated `client_meetings` entity:**

`client_meetings` now includes `client_type` (external or self), Granola sync fields, and links to done items, resource signals, and agendas. Meeting notes and transcripts are synced on demand from **Granola** via its MCP interface — no webhooks or transcript storage required.

---

## IV. Filament UI Structure

### Navigation Groups

| Group | Resources & Pages |
|-------|-------------------|
| 🌅 **Today** | Daily Plan (morning/evening), Today's Tasks, Today's Habits, Time Blocks |
| 🎯 **Goals & Projects** | Goals, Milestones, Projects, Tasks, Task Tree, Client Meetings, Meeting Agendas, Someday/Maybe, Opportunity Pipeline, Budgets |
| 🌿 **Habits** | Habits list, Habit Logs, Streak Dashboard |
| 📓 **Journal** | Journal Entries, Weekly Reviews |
| 📊 **Progress** | Progress Dashboard, Streaks, Goal Metrics, Life Area Scores |
| 🤖 **AI Studio** | AI Interactions log, Manual AI prompts, Goal Breakdown tool |
| ⚙️ **Settings** | Life Areas config, User Preferences, Notification settings |

---

### Key Pages

#### Dashboard — The Daily Command Center

- **Widget:** Today's date + Day Theme
- **Widget:** Morning Checklist (3 top priorities, habit check-ins)
- **Widget:** Time Block timeline — visual schedule for today
- **Widget:** Goal Progress bars — one per active goal, color-coded by life area
- **Widget:** Habit ring — today's habit completion percentage
- **Widget:** AI Daily Intention card — today's AI-generated focus prompt
- **Widget:** Streak highlights — current streaks worth celebrating

#### Today's Plan Page

- Morning session form — set day theme, confirm top 3, review time blocks
- Live task list — drag to reorder, quick-complete inline
- Evening session form — ratings sliders, reflection textarea, AI summary trigger

#### Goals Resource

- Kanban view by status (Active / Paused / Achieved / Abandoned)
- List view filterable by life area and horizon
- Goal detail page: Why statement, Milestones timeline, linked Tasks, Progress chart, AI breakdown button

#### Progress Dashboard

- Life Area Wheel — radar/spider chart showing balance across all six areas
- Weekly progress line chart — tasks completed + habits hit over 8 weeks
- Goal velocity — are you on track to hit target dates?
- Streak leaderboard — your own top habits by streak length
- Monthly heatmap — daily activity and energy ratings

#### Meeting Agendas

- Build agendas before meetings, auto-populate with open tasks and deferred items

#### Meeting Intelligence View

- Post-meeting view showing done items, scope items, action items, and deferred items extracted from transcript

#### Goal Brainstorm

- AI-facilitated session for internal goals

---

## V. AI Features

### Eight AI Integration Points

All interactions are logged and contextualized with your current goals, habits, and recent history.

#### 1. Daily Morning Intention
Each morning, AI generates a short, personalized intention statement — not a to-do list, but a mindset anchor. It references your current top goals, yesterday's reflection, and your energy patterns.

#### 2. Goal Breakdown
When you create or refine a goal, AI analyzes the goal, your timeline, and life area context to suggest milestones and initial daily actions. You review and accept or modify before anything is saved.

#### 3. Daily Plan Builder
AI reviews your open tasks, scheduled habits, and time blocks and suggests an optimized daily plan — prioritizing high-impact, goal-aligned work in your peak energy windows.

#### 4. Evening Summary
After the evening check-in, AI generates a brief summary of the day — what you accomplished, patterns noticed (energy vs. focus, types of work), and a single encouragement or challenge for tomorrow.

#### 5. Weekly Analysis
After the weekly review is submitted, AI analyzes the week across all six life areas — identifying which areas received attention, which were neglected, and whether you're trending toward your goals.

#### 6. Freeform AI Studio
An open chat interface where you can ask questions about your goals, request planning help, brainstorm on creative projects, or get an honest reflection on your progress patterns.

#### 7. Goal Brainstorming
When you create a self-meeting of type `brainstorm` or `planning`, AI acts as a thinking partner — asking clarifying questions, helping define what success looks like, surfacing what resources are needed, and suggesting whether this is a 90-day, 1-year, or lifetime goal.

#### 8. Personal Resource Readiness Assessment
When you consider activating a deferred personal goal, AI reviews your current load — active goals, habit count, recent energy and focus scores — and assesses whether now is the right time, what specifically was missing when you deferred it, and what the first three steps should be.

---

## VI. Daily Workflow — Standard Operating Procedure

### Morning *(15–20 minutes)*

1. Open the Daily Plan page. Review yesterday's completion status.
2. Read your AI Daily Intention. Let it land before moving on.
3. Set your Day Theme — one word or short phrase.
4. Confirm your Top 3 priorities. These must move a goal forward, not just maintain the status quo.
5. Review your time blocks. Protect at least one deep work block.
6. Check today's habits. Know which ones are scheduled before the day begins.
7. **Close the app. Work.**

### During the Day

- Log task completions as they happen — don't save it all for the evening
- Mark habit completions in real time
- If something urgent displaces a priority, note why — this data is valuable
- Use the AI Studio if you hit a decision point or need a planning gut-check

### Evening *(10–15 minutes)*

1. Review task completions. Mark anything remaining as done, deferred, or dropped.
2. Confirm habit log — any missed habits to note?
3. Rate the day: Energy, Focus, Progress (each 1–5).
4. Write your reflection — 2 to 5 sentences minimum. Honesty matters more than positivity.
5. Trigger AI evening summary. Read it.
6. Glance at tomorrow. Make one adjustment if needed. Then close.

---

### Weekly SOP

#### Monday Planning Session *(30–45 min)*

- Open the Weekly Review from last week. Read your own words.
- Review the Goal Progress dashboard — where are you ahead? Behind? Stalled?
- Set this week's intended outcomes — one per life area (or fewer if realistic)
- Schedule your week — time blocks first, then meetings, then fill gaps
- Reset / add habits for the week
- Request the AI Weekly Briefing — review its suggestions

#### Friday / Sunday Review Session *(20–30 min)*

- Score the week honestly across each life area (1–5)
- Log your wins — write at least three, no matter how small
- Log friction — what blocked you, drained you, or didn't work?
- Write the weekly reflection journal entry
- Trigger AI Weekly Analysis — save it with the review
- Carry forward any unfinished outcomes to next week or demote them

---

## VII. Build Phases

### Phase 1 — Foundation *(Weeks 1–4)*

- Laravel project setup with Filament v3
- Database migrations for all 12 core entities
- Seed life areas with defaults
- Filament Resources: Life Areas, Goals, Projects, Tasks
- Basic Daily Plan CRUD
- Simple dashboard with today's tasks

### Phase 2 — Daily Rhythm *(Weeks 5–8)*

- Morning session form and evening session form
- Time Block builder with visual timeline widget
- Habit tracker with daily logging and streak calculation
- Journal entries (daily)
- AI integration: Daily Intention + Evening Summary
- Granola MCP integration — sync meetings on demand
- Basic agenda builder

### Phase 3 — Intelligence *(Weeks 9–12)*

- AI Goal Breakdown tool
- AI Daily Plan Builder
- AI Studio (freeform chat with goal context)
- Weekly Review resource
- AI Weekly Analysis
- Full Meeting Intelligence extraction (done items, deferred items, resource signals)
- Goal brainstorm AI session
- Personal resource readiness assessment

### Phase 4 — Progress & Insight *(Weeks 13–16)*

- Progress Dashboard with charts
- Life Area Wheel (radar chart widget)
- Goal velocity tracking
- Monthly heatmap
- Streak leaderboard and milestone celebrations
- Done item portfolio view (case study and testimonial tracker)
- Pattern detection across deferred items
- Polish, performance, and mobile responsiveness

---

## VIII. Closing Principle

> *In Celtic tradition, the thin place — an caol áit — is where the distance between the mundane and the sacred grows small. Solas Rún is your thin place: where the life you are living and the life you are building become one.*

---

*This document is a living blueprint. As the system is built and used, it will evolve. Return to it often. Update it honestly. Let the system serve you — not the other way around.*

---

*Solas Rún • Version 1.2 • System Design Blueprint*
