# Chapter 14: Dashboard

## Overview

The Solas Run dashboard is a custom Filament dashboard page that displays nine widgets, each providing a focused view into a different aspect of the system. Widgets communicate with each other through Livewire events for real-time updates without full page reloads.

**Dashboard Page Path:** `app/Filament/Pages/Dashboard.php`

**Widget Base Class:** All widgets extend `BaseWidget`, an abstract base class at `app/Filament/Widgets/BaseWidget.php`.

**Widget Blade Views:** `resources/views/filament/widgets/`

---

## Widget Layout

Widgets are ordered by their `sort` property. The following sections describe each widget in display order.

---

### 1. DayThemeWidget

**Sort:** 1
**Type:** StatsOverview

Displays the current date and the day's theme from the active `DailyPlan`. The theme is clickable, opening a modal for editing. Below the theme, it shows yesterday's self-reported ratings for energy, focus, and progress, each on a 1-5 scale.

**Rating Color Coding:**

| Rating | Color |
|---|---|
| 1-2 | Danger (red) |
| 3 | Warning (amber) |
| 4-5 | Success (green) |

---

### 2. MorningChecklistWidget

**Sort:** 2
**Columns:** 2

A two-part morning checklist combining task priorities and habits.

**Priorities Section:**
Displays today's priorities from the active `DailyPlan`. Each priority has a toggle for marking it complete.

- `togglePriority(taskId)` -- Toggles the completion state of a priority task.

**Habits Section:**
Shows active habits with their completion status, life area, and color. Habits are ordered by `time_of_day`.

- `toggleHabit(habitId)` -- Toggles the habit's completion for today.
- Dispatches the `habit-logged` Livewire event when a habit is toggled.

**Morning Completeness Check:**
The `isMorningComplete()` method returns true when the daily plan has all three morning elements set: a theme, a morning intention, and at least priority_1 defined.

---

### 3. TimeBlockTimelineWidget

**Sort:** 3
**Columns:** 1

Displays a visual timeline of today's `TimeBlock` records ordered by `start_time`. Provides links to edit existing time blocks or create new ones, routing to the daily plan resource.

---

### 4. GoalProgressWidget

**Sort:** 4
**Columns:** 2

Lists all active goals alongside their life areas, ordered by `life_area_id`. Uses a custom Blade view for rendering progress indicators per goal.

---

### 5. HabitRingWidget

**Sort:** 5
**Columns:** 1

Renders an SVG circular progress ring representing today's overall habit completion.

**SVG Parameters:**

| Parameter | Value |
|---|---|
| Radius (`r`) | 36 |
| Circumference | 226.2 |

**Completion Calculation:**
`completion % = (completed habits / total active habits) * 100`

Below the ring, the widget displays the top 3 habits ranked by current streak length.

**Event Listener:** Refreshes on the `habit-logged` event to reflect real-time habit completions.

---

### 6. AiIntentionWidget

**Sort:** 6
**Columns:** Full width

Displays the AI-generated morning intention for today's daily plan.

**Behavior:**

- If an intention already exists on the plan, it is displayed directly.
- If no intention exists, a "Generate" button is shown.
- `generate()` calls `AiService::generateMorningIntention()` with a fallback message if the call fails.
- Shows the current plan status alongside the intention text.

---

### 7. StreakHighlightsWidget

**Sort:** 7
**Columns:** 1

Highlights habits with notable streaks and recent achievements.

**Streak List:**
Displays the top 5 habits that have a current streak of 3 or more days. Each entry shows the habit name, streak count, and life area color.

**Personal Best Flags:**
A habit is flagged as a personal best (`isPB`) when the current streak is greater than or equal to the habit's all-time best streak.

**Recent Milestones:**
Lists milestones completed within the last 7 days.

**Event Listener:** Refreshes on the `habit-logged` event.

---

### 8. OpportunityPipelineWidget

**Sort:** 8
**Columns:** 1

Provides a financial and pipeline overview of opportunities.

**Pipeline Metrics:**

| Metric | Description |
|---|---|
| Total Value | Sum of all opportunity values |
| Weighted Value | Sum of values weighted by probability |
| Stage Grouping | Count, value, and weighted value per pipeline stage |

**Action Items:**
- Actions due this week across all pipeline items.
- Overdue deferred items requiring attention.
- Stale high-value items: opportunities that have gone more than 30 days without a review.

---

### 9. DoneDeliveredWidget

**Sort:** 8
**Columns:** 1

Summarizes recent completions and delivered value.

**Content:**

| Section | Description |
|---|---|
| Recent Done Items | The 5 most recent `MeetingDoneItem` records, shown with their meeting relationships |
| Total Value Delivered | Sum of `value_delivered` across all done items |
| Tasks Done This Month | Count of tasks completed in the current calendar month |

---

## Cross-Widget Events

Widgets communicate through Livewire dispatched events for real-time interactivity without page reloads.

### habit-logged

| Property | Detail |
|---|---|
| Dispatched by | `MorningChecklistWidget` (when `toggleHabit` is called) |
| Listened by | `HabitRingWidget`, `StreakHighlightsWidget` |
| Effect | Both listening widgets refresh their data to reflect the updated habit completion state |

This event enables a seamless workflow: when a user checks off a habit in the morning checklist, the habit ring updates its progress arc and the streak highlights re-evaluate personal bests and streak counts, all without a full page reload.
