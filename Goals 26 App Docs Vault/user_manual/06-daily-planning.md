# 06. Daily Planning

Daily Planning is the operational heart of Solas Run. Each day has a single `DailyPlan` that holds your morning intention, top three priorities, time blocks, and evening reflection. The daily workflow moves from intention to action to review.

## Daily Workflow

The daily planning cycle has three phases:

1. **Morning (plan creation)** -- Open the dashboard, review the AI-suggested day theme, set your morning intention, and confirm your top three priorities. Time blocks are laid out for the day.
2. **During the day (execution)** -- Work through time blocks and toggle priorities as you complete them. The MorningChecklistWidget tracks progress in real time.
3. **Evening (reflection)** -- Rate your energy, focus, and progress on a 1--5 scale. Write an evening reflection. Change the plan status to `reviewed`.

This cycle ensures every day begins with clarity and ends with feedback that informs the next day.

---

## DailyPlan

A DailyPlan captures the full arc of a single day -- what you intend, what you do, and what you learn.

### Database Fields

**Table:** `daily_plans`

| Field               | Type          | Constraints                        | Purpose                               |
|---------------------|---------------|------------------------------------|---------------------------------------|
| id                  | bigIncrements | PK                                 | Primary key                           |
| user_id             | foreignId     | FK to users                        | Tenant owner                          |
| plan_date           | date          | required                           | Which day this plan covers            |
| day_theme           | string        | nullable                           | Theme label (e.g., "Deep Work")       |
| morning_intention   | text          | nullable                           | Free-text morning intention           |
| top_priority_1      | foreignId     | nullable, FK to tasks              | First priority task                   |
| top_priority_2      | foreignId     | nullable, FK to tasks              | Second priority task                  |
| top_priority_3      | foreignId     | nullable, FK to tasks              | Third priority task                   |
| ai_morning_prompt   | text          | nullable                           | AI-generated morning prompt           |
| ai_evening_summary  | text          | nullable                           | AI-generated evening summary          |
| energy_rating       | integer       | nullable, 1--5                     | Self-rated energy level               |
| focus_rating        | integer       | nullable, 1--5                     | Self-rated focus level                |
| progress_rating     | integer       | nullable, 1--5                     | Self-rated progress level             |
| evening_reflection  | text          | nullable                           | Free-text evening reflection          |
| status              | enum          | draft, active, reviewed            | Plan lifecycle state                  |
| created_at          | timestamp     |                                    |                                       |
| updated_at          | timestamp     |                                    |                                       |

### Relationships

| Relation        | Type      | Target        | Notes                              |
|-----------------|-----------|---------------|------------------------------------|
| topPriority1    | BelongsTo | Task          | First priority task                |
| topPriority2    | BelongsTo | Task          | Second priority task               |
| topPriority3    | BelongsTo | Task          | Third priority task                |
| timeBlocks      | HasMany   | TimeBlock     | Scheduled blocks for the day       |
| aiInteractions  | HasMany   | AiInteraction | AI conversations about this plan   |

### Scopes and Helpers

| Method / Scope      | Purpose                                                  |
|----------------------|----------------------------------------------------------|
| `today()`            | Scope that finds the plan where `plan_date` = today      |
| `todayOrCreate()`    | Returns today's plan, creating a draft if none exists    |

---

## TimeBlock

Time blocks divide the day into scheduled segments. Each block can optionally link to a task or project for tracking purposes.

### Database Fields

**Table:** `time_blocks`

| Field          | Type          | Constraints                    | Purpose                           |
|----------------|---------------|--------------------------------|-----------------------------------|
| id             | bigIncrements | PK                             | Primary key                       |
| daily_plan_id  | foreignId     | FK to daily_plans              | Parent daily plan                 |
| title          | string        | required                       | Block label                       |
| block_type     | string        | required                       | Category (e.g., work, break)      |
| start_time     | time          | required                       | Block start                       |
| end_time       | time          | required                       | Block end                         |
| task_id        | foreignId     | nullable, FK to tasks          | Linked task                       |
| project_id     | foreignId     | nullable, FK to projects       | Linked project                    |
| notes          | text          | nullable                       | Additional context                |
| color_hex      | string        | nullable                       | Visual color for timeline display |
| created_at     | timestamp     |                                |                                   |
| updated_at     | timestamp     |                                |                                   |

### Relationships

| Relation   | Type      | Target    |
|------------|-----------|-----------|
| dailyPlan  | BelongsTo | DailyPlan |
| task       | BelongsTo | Task      |
| project    | BelongsTo | Project   |

---

## DailyPlanService

**File:** `app/Services/DailyPlanService.php`

| Method                                     | Return       | Purpose                                                                                           |
|--------------------------------------------|--------------|---------------------------------------------------------------------------------------------------|
| `getOrCreateToday(User $user)`             | DailyPlan    | Returns today's plan. If none exists, creates a draft plan for today.                             |
| `buildFromAi(User $user)`                  | DailyPlan    | Creates or updates today's plan using AI suggestions: fetches the top 3 actionable tasks, sets the day theme via `suggestDayTheme()`, and activates the plan. |
| `getTopPriorities(DailyPlan $plan)`        | Collection   | Returns a collection of the three priority tasks (filters out null slots).                        |
| `completePriority(DailyPlan $plan, int $index)` | void   | Marks the priority at the given index (1, 2, or 3) as done and propagates the completion upward through the task tree. |
| `suggestDayTheme()`                        | string       | Returns a theme string based on the current day of the week.                                      |

### Day Theme Suggestions

The `suggestDayTheme()` method returns a default theme for each weekday:

| Day       | Theme              |
|-----------|--------------------|
| Monday    | Focus & Planning   |
| Tuesday   | Deep Work          |
| Wednesday | Deep Work          |
| Thursday  | Collaboration      |
| Friday    | Review & Ship      |
| Saturday  | Creative & Growth  |
| Sunday    | Rest & Reflection  |

---

## Filament Resource

**Navigation:** Today > Daily Plans (sort 1)

### Form Sections

**Morning Session:**

| Field              | Type           | Details                                              |
|--------------------|----------------|------------------------------------------------------|
| plan_date          | Date picker    | Required                                             |
| day_theme          | Text input     | Nullable, placeholder with suggested theme           |
| morning_intention  | Textarea       | Free-text morning intention                          |
| top_priority_1     | Select         | Searchable, FK to tasks                              |
| top_priority_2     | Select         | Searchable, FK to tasks                              |
| top_priority_3     | Select         | Searchable, FK to tasks                              |
| ai_morning_prompt  | Placeholder    | AI-generated content, not directly editable          |

**Evening Session:**

| Field              | Type           | Details                                              |
|--------------------|----------------|------------------------------------------------------|
| energy_rating      | Select         | 1--5 scale                                           |
| focus_rating       | Select         | 1--5 scale                                           |
| progress_rating    | Select         | 1--5 scale                                           |
| evening_reflection | Textarea       | Free-text evening reflection                         |
| ai_evening_summary | Placeholder    | AI-generated content, not directly editable          |
| status             | Select         | draft, active, reviewed                              |

### Table Columns

| Column           | Display                                                    |
|------------------|------------------------------------------------------------|
| plan_date        | Bold, sortable                                             |
| day_theme        | Badge                                                      |
| energy_rating    | Numeric                                                    |
| focus_rating     | Numeric                                                    |
| progress_rating  | Numeric                                                    |
| status           | Color-coded badge: draft=gray, active=warning, reviewed=success |

**Default sort:** `plan_date` descending.

### Relation Manager

- **TimeBlocksRelationManager** -- View and manage time blocks belonging to this daily plan.

---

## Dashboard Widgets

### DayThemeWidget (sort 1)

**Type:** StatsOverview

Displays today's date and the current day theme. Also shows yesterday's energy, focus, and progress ratings with color coding:

| Rating Value | Color   |
|--------------|---------|
| 1--2         | danger  |
| 3            | warning |
| 4--5         | success |

If no plan exists for yesterday, the stats display as empty.

### MorningChecklistWidget (sort 2, 2 columns)

Shows today's top three priorities and active habits as a checklist. Provides interactive toggles:

| Method            | Purpose                                                 |
|-------------------|---------------------------------------------------------|
| `togglePriority`  | Marks a priority task as done and propagates upward     |
| `toggleHabit`     | Logs a habit for today and triggers streak recalculation |

Dispatches the `habit-logged` event when a habit is toggled, which refreshes the HabitRingWidget and StreakHighlightsWidget.

### TimeBlockTimelineWidget (sort 3, 1 column)

Renders a vertical timeline visualization of today's time blocks, ordered by `start_time`. Each block displays its title, time range, and optional color from `color_hex`.

### AiIntentionWidget (sort 6, full width)

Generates a morning intention using AI:

1. Calls `AiService::generateMorningIntention()` to produce a personalized prompt based on today's plan, priorities, and recent context.
2. If the AI service is unavailable or returns an error, falls back to a canned intention string.
3. The generated intention is stored in the `ai_morning_prompt` field on the DailyPlan.

---

## Status Lifecycle

A daily plan moves through three states:

```
draft  -->  active  -->  reviewed
```

| Status   | Meaning                                                       |
|----------|---------------------------------------------------------------|
| draft    | Plan created but not yet committed to. Morning session incomplete. |
| active   | Morning session complete. Working through the day.            |
| reviewed | Evening reflection done. Day is closed.                       |

Plans default to `draft` on creation. The `buildFromAi()` method sets the plan to `active`. Moving to `reviewed` is a manual action during the evening session.
