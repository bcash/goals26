# 07. Habits

Habits are recurring actions that compound over time. Unlike tasks (which are completed once), habits are performed repeatedly and tracked through streaks. Every habit belongs to a Life Area, grounding your routines in the same framework as your goals.

## Purpose

Habits bridge the gap between ambition and daily behavior. A goal like "Improve fitness" only succeeds if the supporting habits ("Run 3x/week", "Stretch daily") are consistently executed. The streak system provides visible momentum, and the dashboard widgets surface completion rates so nothing slips quietly.

---

## Habit

### Database Fields

**Table:** `habits`

| Field          | Type          | Constraints                                    | Purpose                                |
|----------------|---------------|------------------------------------------------|----------------------------------------|
| id             | bigIncrements | PK                                             | Primary key                            |
| user_id        | foreignId     | FK to users                                    | Tenant owner                           |
| life_area_id   | foreignId     | FK to life_areas                               | Which life area this supports          |
| title          | string        | required                                       | Habit name                             |
| description    | text          | nullable                                       | Detailed description or motivation     |
| frequency      | enum          | daily, weekdays, weekly, custom                | How often the habit should be done     |
| target_days    | json          | nullable                                       | Array of day numbers for custom frequency (0=Sunday, 1=Monday, ..., 6=Saturday) |
| time_of_day    | string        | nullable                                       | When to perform (e.g., morning, evening) |
| status         | enum          | active, paused                                 | Whether the habit is currently tracked |
| streak_current | integer       | default: 0                                     | Current consecutive streak             |
| streak_best    | integer       | default: 0                                     | All-time best streak                   |
| started_at     | date          | nullable                                       | When habit tracking began              |
| created_at     | timestamp     |                                                |                                        |
| updated_at     | timestamp     |                                                |                                        |

### Relationships

| Relation  | Type      | Target    | Notes                                    |
|-----------|-----------|-----------|------------------------------------------|
| lifeArea  | BelongsTo | LifeArea  | Parent life area                         |
| habitLogs | HasMany   | HabitLog  | All log entries for this habit           |
| todayLog  | HasOne    | HabitLog  | Today's log entry (scoped to today's date) |

### Frequency Types

| Frequency | Behavior                                                                 |
|-----------|--------------------------------------------------------------------------|
| daily     | Expected every calendar day                                              |
| weekdays  | Expected Monday through Friday; weekends are skipped (not streak-breaking) |
| weekly    | Expected at least once per 7-day period                                  |
| custom    | Expected only on days listed in `target_days`                            |

---

## HabitLog

A HabitLog records whether a habit was completed or skipped on a given day.

### Database Fields

**Table:** `habit_logs`

| Field       | Type          | Constraints                  | Purpose                        |
|-------------|---------------|------------------------------|--------------------------------|
| id          | bigIncrements | PK                           | Primary key                    |
| habit_id    | foreignId     | FK to habits                 | Parent habit                   |
| logged_date | date          | required                     | Which day this log covers      |
| status      | enum          | completed, skipped           | Whether the habit was done     |
| note        | text          | nullable                     | Optional context or reflection |
| created_at  | timestamp     |                              |                                |
| updated_at  | timestamp     |                              |                                |

### Relationships

| Relation | Type      | Target |
|----------|-----------|--------|
| habit    | BelongsTo | Habit  |

---

## HabitStreakService

**File:** `app/Services/HabitStreakService.php`

The HabitStreakService handles all streak calculations and habit logging.

### Methods

| Method                                          | Return     | Purpose                                                                                                |
|-------------------------------------------------|------------|--------------------------------------------------------------------------------------------------------|
| `calculateStreak(Habit $habit)`                 | int        | Calculates the current streak length based on the habit's frequency. See frequency-specific logic below. |
| `recalculate(Habit $habit)`                     | int        | Alias for `calculateStreak()`. Updates `streak_current` and `streak_best` on the habit.                |
| `logToday(Habit $habit)`                        | HabitLog   | Creates or updates today's log entry as `completed`, then calls `recalculate()` to update the streak.  |
| `isCompletedToday(Habit $habit)`                | bool       | Returns true if a `completed` log exists for today.                                                    |
| `getCompletionRate(Habit $habit, int $days = 30)` | float   | Returns the completion rate over the last `$days` as a float between 0.0 and 1.0.                     |
| `getActiveStreaks(User $user)`                  | Collection | Returns all active habits with their streak data, sorted by streak length descending.                  |

### Streak Calculation Logic

Streak calculation varies by frequency:

| Frequency | Logic                                                                                           |
|-----------|-------------------------------------------------------------------------------------------------|
| daily     | Counts consecutive calendar days backward from today where a `completed` log exists. A single missing day breaks the streak. |
| weekdays  | Counts consecutive weekdays backward. Saturday and Sunday are skipped entirely and do not break the streak. |
| weekly    | Checks each 7-day period backward. The streak continues as long as at least one `completed` log exists in each period. |
| custom    | Only counts days that appear in `target_days`. Non-target days are skipped and do not break the streak. |

### Helper Methods

| Method                              | Purpose                                                          |
|-------------------------------------|------------------------------------------------------------------|
| `countEligibleDays(Habit, period)`  | Counts how many days in the given period are eligible for this habit based on its frequency. |
| `isDayEligible(Habit, Carbon date)` | Returns true if the given date is an eligible day for this habit. |

---

## Filament Resource

**Navigation:** Habits > Habits (sort 1)

### Form Sections

**Habit Details:**

| Field        | Type           | Details                                     |
|--------------|----------------|---------------------------------------------|
| title        | Text input     | Required                                    |
| life_area_id | Select         | Required, searchable                        |
| description  | Textarea       | Optional motivation or notes                |

**Schedule:**

| Field        | Type                | Details                                                         |
|--------------|---------------------|-----------------------------------------------------------------|
| frequency    | Select              | daily, weekdays, weekly, custom                                 |
| time_of_day  | Select              | morning, afternoon, evening, anytime                            |
| target_days  | Checkbox list       | 7 columns (Sun--Sat); visible only when frequency = custom      |
| status       | Select              | active, paused                                                  |
| started_at   | Date picker         | When tracking began                                             |

### Table Columns

| Column         | Display                                              |
|----------------|------------------------------------------------------|
| title          | Searchable, bold                                     |
| life_area      | Badge (colored by life area)                         |
| frequency      | Color-coded badge                                    |
| time_of_day    | Text                                                 |
| streak_current | Warning color                                        |
| streak_best    | Text                                                 |
| status         | Badge                                                |

**Default sort:** `streak_current` descending.

### Filters

- **status** -- Active or paused
- **life_area_id** -- Filter by life area

### Actions

| Action     | Behavior                                                                   |
|------------|----------------------------------------------------------------------------|
| log_today  | Creates a `completed` HabitLog for today and triggers streak recalculation |
| edit       | Opens the edit form                                                        |
| delete     | Deletes the habit and its logs                                             |

---

## Dashboard Widgets

### HabitRingWidget (sort 5, 1 column)

Displays a circular SVG progress ring showing today's habit completion percentage.

**Ring geometry:**
- Radius: 36
- Circumference: 226.2
- Stroke dash offset calculated as `circumference * (1 - completionPercent)`

**Content:**
- Completion percentage in the center of the ring
- Top 3 habits listed below the ring, sorted by current streak length

**Events:** Listens for the `habit-logged` event and refreshes automatically.

### StreakHighlightsWidget (sort 7, 1 column)

Highlights habits with notable streaks.

**Content:**
- Top 5 habits with `streak_current >= 3`, color-coded by their life area
- Personal best indicator: an `isPB` flag is set to true when `streak_current >= streak_best`, marking an active personal best
- Recent milestones: habits that were completed within the last 7 days and reached a new best streak

**Events:** Listens for the `habit-logged` event and refreshes automatically.

---

## Streak Behavior Examples

**Daily habit, no misses:**
```
Mon(done) Tue(done) Wed(done) Thu(done) Fri(done) = streak 5
```

**Weekday habit over a weekend:**
```
Thu(done) Fri(done) Sat(skip) Sun(skip) Mon(done) = streak 3
```
Saturday and Sunday do not break the streak because they are not eligible days.

**Custom habit (target days: Monday, Wednesday, Friday):**
```
Mon(done) Tue(n/a) Wed(done) Thu(n/a) Fri(done) = streak 3
```
Tuesday and Thursday are not target days and are ignored.

**Weekly habit:**
```
Week 1: Wed(done) | Week 2: Mon(done) | Week 3: Fri(done) = streak 3
```
As long as at least one completion exists per 7-day period, the streak holds.
