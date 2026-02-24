# 11. Deferral Pipeline

The deferral pipeline transforms "not now" decisions into a structured system for tracking, reviewing, and eventually acting on deferred work and commercial opportunities. Items enter the pipeline from task deferrals, meeting scope decisions, personal goals, and ad-hoc ideas. Regular reviews prevent items from being forgotten.

## Purpose

Saying "no" or "not yet" is a core productivity skill, but most systems treat deferred work as dead. The deferral pipeline keeps deferred items alive with review schedules, commercial value tracking, and promotion paths back into active work when the time is right.

## DeferredItem Model

### Database Fields

**Table:** `deferred_items`

| Field                  | Type          | Constraints                              | Purpose                                    |
|------------------------|---------------|------------------------------------------|--------------------------------------------|
| id                     | bigIncrements | PK                                       | Primary key                                |
| user_id                | foreignId     | FK to users                              | Tenant owner                               |
| task_id                | foreignId     | nullable, FK to tasks                    | Originating task (if deferred from a task) |
| project_id             | foreignId     | nullable, FK to projects                 | Associated project                         |
| meeting_id             | foreignId     | nullable, FK to client_meetings          | Meeting where this was deferred            |
| scope_item_id          | foreignId     | nullable, FK to meeting_scope_items      | Scope item that triggered the deferral     |
| title                  | string        | required                                 | What was deferred                          |
| description            | text          | nullable                                 | Detailed description                       |
| client_context         | text          | nullable                                 | Client-side context for the deferral       |
| why_it_matters         | text          | nullable                                 | Why this item has future value             |
| client_name            | string        | nullable                                 | Client associated with this item           |
| client_quote           | text          | nullable                                 | Direct quote from the client               |
| deferral_reason        | string        |                                          | Why it was deferred                        |
| opportunity_type       | string        |                                          | Type of opportunity this represents        |
| client_type            | string        |                                          | External or internal/self                  |
| estimated_value        | decimal       | nullable                                 | Estimated dollar value of the opportunity  |
| value_notes            | text          | nullable                                 | Explanation of the value estimate          |
| status                 | enum          |                                          | Current pipeline stage                     |
| deferred_on            | date          |                                          | When the item was deferred                 |
| revisit_date           | date          | nullable                                 | Scheduled date for next review             |
| revisit_trigger        | string        | nullable                                 | Event or condition that triggers revisit   |
| last_reviewed_at       | datetime      | nullable                                 | When the item was last reviewed            |
| review_count           | integer       | default: 0                               | Number of times reviewed                   |
| ai_opportunity_analysis| text          | nullable                                 | AI-generated opportunity analysis          |
| resource_requirements  | json          | nullable                                 | Resources needed to execute                |
| resource_check_done    | boolean       |                                          | Whether resource feasibility was assessed  |

### Relationships

| Relation          | Type      | Target             |
|-------------------|-----------|--------------------|
| task              | BelongsTo | Task               |
| project           | BelongsTo | Project            |
| meeting           | BelongsTo | ClientMeeting      |
| scopeItem         | BelongsTo | MeetingScopeItem   |
| opportunityPipeline | HasOne  | OpportunityPipeline|
| reviews           | HasMany   | DeferralReview     |

### Scopes

| Scope                | Purpose                                                                 |
|----------------------|-------------------------------------------------------------------------|
| `dueForReview()`     | Scheduled items past their revisit date, or someday items not reviewed in 30 days |
| `hasCommercialValue()` | Items with a non-null estimated_value                                |
| `byStage()`          | Filter by pipeline status/stage                                        |

### Helper Methods

| Method          | Returns  | Purpose                                                    |
|-----------------|----------|------------------------------------------------------------|
| `weightedValue()` | decimal | Estimated value adjusted by pipeline stage probability     |
| `isOverdue()`   | boolean  | True if revisit_date is in the past and not yet reviewed   |
| `promote()`     | void     | Move the item back into active work (creates/updates task) |

## DeferralReview Model

Each time a deferred item is reviewed, a DeferralReview record captures the outcome and decision.

### Database Fields

**Table:** `deferral_reviews`

| Field             | Type          | Constraints                     | Purpose                              |
|-------------------|---------------|---------------------------------|--------------------------------------|
| id                | bigIncrements | PK                              | Primary key                          |
| user_id           | foreignId     | FK to users                     | Tenant owner                         |
| deferred_item_id  | foreignId     | FK to deferred_items            | The item being reviewed              |
| reviewed_on       | date          |                                 | Date of this review                  |
| outcome           | string        |                                 | Decision made (keep, reschedule, etc)|
| next_revisit_date | date          | nullable                        | When to check again                  |
| review_notes      | text          | nullable                        | Notes about the decision             |
| context_update    | text          | nullable                        | Updated context since last review    |

### Relationships

| Relation     | Type      | Target       |
|--------------|-----------|--------------|
| deferredItem | BelongsTo | DeferredItem |

## DeferralService

The `DeferralService` provides the capture, review, and management logic for the pipeline.

### Capture Methods

| Method                    | Purpose                                                       |
|---------------------------|---------------------------------------------------------------|
| `deferTask()`             | Defer an existing task into the pipeline                      |
| `captureFromScopeItem()`  | Create a deferred item from a meeting scope item              |
| `captureIdea()`           | Capture a freestanding idea for future consideration          |
| `capturePersonalGoal()`   | Capture a personal goal that cannot be pursued yet            |

### Review Methods

| Method                           | Purpose                                                          |
|----------------------------------|------------------------------------------------------------------|
| `getWeeklyReviewItems(User)`     | Returns deferred items grouped into: overdue, scheduled, someday, commercial |
| `submitReview()`                 | Record a review outcome and update the deferred item accordingly |

### Helper Methods

| Method                          | Purpose                                                       |
|---------------------------------|---------------------------------------------------------------|
| `mapScopeTypeToDeferralReason()`| Convert a scope item type to a deferral reason                |
| `detectDeferralReason()`        | Infer the deferral reason from context                        |
| `flagForProposal()`             | Mark an item as ready for a client proposal                   |
| `queueOpportunityAnalysis()`    | Queue AI analysis of the opportunity value                    |

## Filament Resource

**Navigation:** Planning

### Form Layout

**Section 1: Deferred Item**
- Title (required)
- Project (select, nullable)
- Client Name (text input)
- Description (textarea)
- Client Context (textarea)
- Client Quote (textarea)
- Why It Matters (textarea)

**Section 2: Classification & Timing**
- Client Type (select)
- Deferral Reason (select)
- Opportunity Type (select)
- Estimated Value (decimal input)
- Revisit Date (date picker)
- Status (select)
- Revisit Trigger (text input)
- Value Notes (textarea)

**Section 3: Personal Resources Required**

Visible only for internal/self items. Captures the resources needed to execute:
- Time requirements
- Money requirements
- Capability requirements
- Technology requirements
- Energy level needed
- Dependencies
- Resource Check Done (toggle)

**Section 4: AI Opportunity Analysis**
- Read-only text field, populated by AI

### Table Columns

| Column           | Format                                  |
|------------------|-----------------------------------------|
| Title            | With client name displayed alongside    |
| Opportunity Type | Badge                                   |
| Deferral Reason  | Badge                                   |
| Estimated Value  | Money format (dollars)                  |
| Status           | Badge                                   |
| Revisit Date     | Date                                    |
| Review Count     | Integer                                 |

**Default sort:** `estimated_value` descending (highest value first)

### Filters

| Filter               | Purpose                                          |
|----------------------|--------------------------------------------------|
| Opportunity Type     | Filter by type of opportunity                    |
| Status               | Filter by pipeline stage                         |
| Overdue for Review   | Items past their revisit date without a review   |
| High Value (>$5k)    | Items with estimated_value greater than $5,000   |

### Actions

**Review Action**

The primary action on a deferred item. Opens a form with:
- Outcome (select: keep, reschedule, promote, propose, archive)
- Next Revisit Date (date picker, shown for keep/reschedule)
- Review Notes (textarea)

Submitting a review creates a `DeferralReview` record, increments the `review_count`, updates `last_reviewed_at`, and adjusts the `revisit_date` based on the outcome.

**Promote Action**

Moves the deferred item back into active work by creating or reactivating a task. The item's status is updated to reflect the promotion.

**Standard Actions**
- View, Edit

### Pages

- List, Create, View, Edit

## OpportunityPipelineWidget

**Dashboard position:** Sort 8, 1 column span

The Opportunity Pipeline widget provides a high-level view of the commercial deferral pipeline:

- **Total value** -- Sum of `estimated_value` across all active deferred items
- **Weighted value** -- Sum of `weightedValue()` across all active items, adjusted by stage probability
- **Grouped by stage** -- Items organized by their current pipeline status, with counts and subtotals per stage
- **Actions due this week** -- Deferred items with a `revisit_date` in the current week
- **Overdue count** -- Number of items past their revisit date without a recent review
- **Stale high-value items** -- Items with `estimated_value` above zero that have not been reviewed in more than 30 days

## Pipeline Workflow

### Entry Points

Items enter the deferral pipeline through four paths:

1. **Task deferral.** When a task's status is set to "deferred," the `DeferralService.deferTask()` method captures it in the pipeline with the task's context, project, and deferral reason.

2. **Meeting scope decisions.** Out-of-scope items and resource signals from meetings flow into the pipeline via `captureFromScopeItem()`. The scope type maps to a deferral reason automatically.

3. **Ad-hoc ideas.** The `captureIdea()` method creates a deferred item from scratch for ideas that surface outside of meetings or task work.

4. **Personal goals.** The `capturePersonalGoal()` method captures goals that require resources you do not currently have (time, money, capability, technology).

### Review Cycle

The `getWeeklyReviewItems()` method returns items grouped into four categories for the weekly review:

| Category   | Criteria                                                  |
|------------|-----------------------------------------------------------|
| Overdue    | Items past their revisit date that have not been reviewed |
| Scheduled  | Items with a revisit date in the coming week              |
| Someday    | Items with no revisit date, not reviewed in 30+ days      |
| Commercial | Items with a non-null estimated_value, sorted by value    |

During the review, each item receives one of five outcomes:

| Outcome    | Effect                                                         |
|------------|----------------------------------------------------------------|
| Keep       | Maintain the item in the pipeline, set a new revisit date      |
| Reschedule | Push the revisit date forward                                  |
| Promote    | Move the item back into active work as a task                  |
| Propose    | Flag the item for inclusion in a client proposal               |
| Archive    | Remove the item from active review                             |

### Value Tracking

The `estimated_value` field captures the potential dollar value of an opportunity. The `weightedValue()` method adjusts this by the pipeline stage probability, giving a realistic view of the pipeline's worth. The `value_notes` field provides context for the estimate.

Items with commercial value above $5,000 are surfaced by the "High Value" filter and flagged in the dashboard widget if they go stale (more than 30 days without a review).
