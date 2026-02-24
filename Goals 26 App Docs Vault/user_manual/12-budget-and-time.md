# Chapter 12: Budget and Time Tracking

## Overview

Solas Run provides project-level budgeting and time tracking through two models: **ProjectBudget** and **TimeEntry**. Together with the cost fields on individual tasks, these form a financial layer that lets you plan, track, and alert on project spend. Both Filament resources appear under the **Finance** navigation group.

---

## ProjectBudget Model

**Table:** `project_budgets`

### Fields

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | bigint | PK, auto-increment | Primary key |
| `user_id` | bigint unsigned | FK to `users.id` | Owning user |
| `project_id` | bigint unsigned | FK to `projects.id`, required | Associated project |
| `budget_type` | enum | `fixed`, `hourly`, `retainer` | How the budget is structured |
| `budget_total` | decimal(10,2) | | Total budget in dollars |
| `hourly_rate` | decimal(8,2) | nullable | Rate per hour (relevant for hourly/retainer types) |
| `estimated_hours` | decimal(8,2) | nullable | Estimated total hours for the engagement |
| `actual_spend` | decimal(10,2) | default `0` | Running total of recorded spend |
| `estimated_remaining` | decimal(10,2) | nullable | Projected remaining budget |
| `burn_rate` | decimal(8,2) | nullable | Current burn rate |
| `alert_threshold_percent` | integer | nullable, 0-100 | Percentage at which alerts trigger |
| `notes` | text | nullable | Freeform notes about the budget |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |

### Money Storage

All monetary values are stored as **decimal dollars** (e.g., `1250.00` means twelve hundred and fifty dollars). They are NOT stored in cents. This applies to `budget_total`, `hourly_rate`, `actual_spend`, `estimated_remaining`, and `burn_rate`.

### Relationships

- `belongsTo` **Project** via `project_id`

### Helper Methods

| Method | Return Type | Logic |
|---|---|---|
| `percentUsed()` | float | `(actual_spend / budget_total) * 100` |
| `isOverBudget()` | bool | `actual_spend > budget_total` |
| `isNearAlert()` | bool | `percentUsed() >= alert_threshold_percent` (returns false if threshold is null) |

---

## TimeEntry Model

**Table:** `time_entries`

### Fields

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | bigint | PK, auto-increment | Primary key |
| `user_id` | bigint unsigned | FK to `users.id` | Owning user |
| `project_id` | bigint unsigned | FK to `projects.id`, nullable | Associated project |
| `task_id` | bigint unsigned | FK to `tasks.id`, nullable | Associated task |
| `description` | string | | What was worked on |
| `hours` | decimal | | Hours logged |
| `logged_date` | date | | Date the work was performed |
| `billable` | boolean | default `true` | Whether this entry is billable |
| `hourly_rate` | decimal | nullable | Rate applied to this entry |
| `cost` | decimal | nullable | Calculated cost for this entry |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |

### Relationships

- `belongsTo` **Task** via `task_id`
- `belongsTo` **Project** via `project_id`

---

## ProjectBudget Filament Resource

**Navigation Group:** Finance

### Form Layout

The form is organized into three sections:

#### Budget Setup

| Field | Type | Notes |
|---|---|---|
| Project | Select | Required. Relationship select for projects. |
| Budget Type | Select | Enum: `fixed`, `hourly`, `retainer` |
| Budget Total | Numeric | Monetary input |
| Hourly Rate | Numeric | Conditionally visible when budget_type is `hourly` or `retainer` |
| Estimated Hours | Numeric | Conditionally visible alongside hourly rate |

#### Tracking

| Field | Type | Notes |
|---|---|---|
| Actual Spend | Numeric | Disabled (read-only). Updated by time entries and task costs. |
| Estimated Remaining | Numeric | Disabled (read-only). Computed value. |
| Burn Rate | Numeric | Disabled (read-only). Computed value. |
| Alert Threshold % | Numeric | Range 0-100. Sets the percentage at which budget warnings appear. |

#### Notes

| Field | Type | Notes |
|---|---|---|
| Notes | Textarea | Freeform budget notes. |

### Table Columns

| Column | Display | Notes |
|---|---|---|
| Project Name | Text | From the project relationship |
| Budget Type | Badge | Styled enum badge |
| Budget Total | Money | Formatted as currency |
| Actual Spend | Money | Formatted as currency |
| Burn % | Percentage | Color-coded: **danger** when >100%, **warning** when > alert threshold %, **success** otherwise |

Default sort: `created_at` descending.

### Filters

- **Budget Type**: Filter by `fixed`, `hourly`, or `retainer`.

---

## TimeEntry Filament Resource

**Navigation Group:** Finance

### Form Layout

| Field | Type | Notes |
|---|---|---|
| Task | Select | Nullable. Relationship select for tasks. |
| Project | Select | Nullable. Relationship select for projects. |
| Description | TextInput | Maximum 255 characters. |
| Hours | Numeric | Step `0.25`, minimum `0.01`. |
| Hourly Rate | Numeric | Prefix `$`. |
| Logged Date | DatePicker | Defaults to today. |
| Billable | Toggle | Defaults to `true`. |

### Table Columns

| Column | Display | Notes |
|---|---|---|
| Description | Text | Word-wrapped, limited to 40 characters |
| Task Title | Text | From the task relationship |
| Project Name | Text | From the project relationship |
| Hours | Numeric | |
| Billable | Icon | Boolean icon column |
| Cost | Money | Calculated value (hours x hourly_rate) |
| Logged Date | Date | |

Default sort: `logged_date` descending.

### Filters

- **Project**: Filter time entries by project.
- **Billable Only**: Toggle to show only billable entries.

---

## Task-Level Cost Integration

Individual tasks carry their own cost fields that feed into the broader project budget picture:

| Task Field | Type | Description |
|---|---|---|
| `estimated_cost` | decimal | Planned cost for the task |
| `actual_cost` | decimal | Recorded cost once work is done |
| `billable` | boolean | Whether the task's cost is billable to the client |

### How Task Costs Relate to Project Budgets

- When a task belongs to a project that has a `ProjectBudget`, the task's `actual_cost` contributes to the budget's `actual_spend`.
- The `billable` flag on a task determines whether its cost is included in client-facing budget reports.
- The `estimated_cost` field on tasks can be summed at the project level to compare against `budget_total` for forecasting purposes.
- Time entries linked to a task (via `task_id`) provide the granular hour-by-hour record behind the task's `actual_cost`.

This layered approach means you can track costs at three levels of granularity:

1. **Time Entry level** -- individual work sessions with hours and rates.
2. **Task level** -- estimated and actual cost per deliverable.
3. **Project Budget level** -- the overall financial envelope with alert thresholds and burn tracking.
