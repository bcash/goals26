# 04. Task Tree

The task tree is the central work management structure in Solas Rún. Tasks form a hierarchy where root tasks represent major deliverables and leaf tasks are the actionable items you complete.

## Tree Structure

```
Root Task (depth 0, is_leaf=false)
  Child Task A (depth 1, is_leaf=false)
    Leaf Task A1 (depth 2, is_leaf=true)  <-- actionable
    Leaf Task A2 (depth 2, is_leaf=true)  <-- actionable
  Child Task B (depth 1, is_leaf=true)    <-- actionable
```

### Key Concepts

- **Root tasks** have `parent_id = null`
- **Leaf tasks** have `is_leaf = true` -- these are the items you actually do
- **Parent tasks** have children and aggregate their progress
- **Path** is a materialized string (e.g., `"1/4/12/"`) for efficient ancestor/descendant queries
- **Depth** is the nesting level (0 for roots)

## Database Fields

**Table:** `tasks`

| Field                      | Type          | Constraints                                    | Purpose                         |
|----------------------------|---------------|------------------------------------------------|---------------------------------|
| id                         | bigIncrements | PK                                             | Primary key                     |
| user_id                    | foreignId     | FK to users                                    | Tenant owner                    |
| life_area_id               | foreignId     | nullable, FK to life_areas                     | Life area assignment            |
| project_id                 | foreignId     | nullable, FK to projects                       | Parent project                  |
| goal_id                    | foreignId     | nullable, FK to goals                          | Linked goal                     |
| milestone_id               | foreignId     | nullable, FK to milestones                     | Linked milestone                |
| parent_id                  | foreignId     | nullable, FK to tasks                          | Parent task (tree)              |
| depth                      | integer       | default: 0                                     | Nesting level                   |
| path                       | string(500)   | nullable                                       | Materialized path "1/4/12/"     |
| is_leaf                    | boolean       | default: true                                  | Actionable leaf node?           |
| sort_order                 | integer       | default: 0                                     | Ordering among siblings         |
| title                      | string        | required                                       | Task name                       |
| notes                      | text          | nullable                                       | Description/context             |
| plan                       | text          | nullable                                       | AI implementation plan          |
| context                    | text          | nullable                                       | AI working context              |
| acceptance_criteria        | text          | nullable                                       | Definition of done              |
| technical_requirements     | text          | nullable                                       | Tech constraints                |
| dependencies_description   | text          | nullable                                       | What must happen first          |
| status                     | enum          | todo, in-progress, done, deferred, blocked     | Current state                   |
| priority                   | enum          | low, medium, high, critical                    | Urgency level                   |
| due_date                   | date          | nullable                                       | Deadline                        |
| scheduled_date             | date          | nullable                                       | Planned work date               |
| time_estimate_minutes      | integer       | nullable                                       | Estimated effort                |
| is_daily_action            | boolean       | default: false                                 | Pinned to today's plan?         |
| estimated_cost             | decimal(10,2) | nullable                                       | Estimated cost (dollars)        |
| actual_cost                | decimal(10,2) | nullable                                       | Actual cost (dollars)           |
| billable                   | boolean       | default: false                                 | Client-billable?                |
| two_minute_check           | boolean       | default: false                                 | Passes 2-minute threshold?      |
| decomposition_status       | enum          | needs_breakdown, in_progress, complete         | Decomposition state             |
| quality_gate_status        | enum          | pending, passed, failed, needs_review          | Quality gate state              |
| deferral_reason            | enum          | nullable                                       | Why it was deferred             |
| deferral_note              | text          | nullable                                       | Deferral context                |
| revisit_date               | date          | nullable                                       | When to revisit                 |
| deferral_trigger           | string        | nullable                                       | What triggers revisit           |
| has_opportunity            | boolean       | default: false                                 | Has commercial opportunity?     |
| created_at                 | timestamp     |                                                |                                 |
| updated_at                 | timestamp     |                                                |                                 |

## Relationships

| Relation      | Type      | Target           |
|---------------|-----------|------------------|
| lifeArea      | BelongsTo | LifeArea         |
| project       | BelongsTo | Project          |
| goal          | BelongsTo | Goal             |
| milestone     | BelongsTo | Milestone        |
| parent        | BelongsTo | Task             |
| children      | HasMany   | Task             |
| descendants   | HasMany   | Task (via path)  |
| qualityGates  | HasMany   | TaskQualityGate  |
| timeEntries   | HasMany   | TimeEntry        |
| deferredItem  | HasOne    | DeferredItem     |

## TaskTreeService

The `TaskTreeService` handles all tree operations:

| Method              | Purpose                                           |
|---------------------|---------------------------------------------------|
| `addChild()`        | Add a child task, update parent's is_leaf         |
| `rebuildPath()`     | Recalculate materialized path for a task          |
| `getTree()`         | Load full tree for a project or goal              |
| `nestChildren()`    | Build nested collection from flat query           |
| `completeLeaf()`    | Mark a leaf done and propagate upward             |
| `propagateUpward()` | Check if all siblings done, trigger quality gate  |
| `getActionableLeaves()` | Get all schedulable leaf tasks               |
| `getNeedsBreakdown()` | Get tasks needing decomposition               |
| `reorder()`         | Reorder siblings by ID array                      |
| `getDeferredBranches()` | Get deferred tasks with context              |

## Scopes

| Scope           | Purpose                                     |
|-----------------|---------------------------------------------|
| `actionable`    | Leaf + two_minute_check + todo/in-progress  |
| `needsBreakdown`| decomposition_status = needs_breakdown      |
| `overdue`       | Has due_date in the past, not done          |
| `deferred`      | Status = deferred                           |
| `dailyActions`  | is_daily_action = true, not done            |
| `leaves`        | is_leaf = true                              |
| `roots`         | parent_id = null                            |

## Quality Gates

When all sibling leaf tasks under a parent are marked done, the `QualityGateService` automatically triggers a quality gate review on the parent. This prevents premature closure of parent tasks.

## Filament Resource

**Navigation:** Goals & Projects > Tasks

### Form Sections

1. **Task** -- title, life area, project, goal, milestone, notes
2. **Specification** (collapsible) -- acceptance_criteria, technical_requirements, dependencies_description
3. **AI Memory** (collapsible) -- plan, context
4. **Scheduling & Priority** -- status, priority, dates, time estimate, daily action toggle

### Table Columns

- Title (with project description), Life Area (badge), Priority (color badge), Status (color badge), Scheduled Date, Due Date (danger if overdue), Daily Action icon

### Filters

- Life Area, Status, Priority, Daily Actions Only, Overdue, Project, Goal

### Actions

- Complete (marks as done), Edit, Delete
- Bulk: Mark Done, Delete
