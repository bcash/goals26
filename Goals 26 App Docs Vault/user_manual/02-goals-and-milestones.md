# 02. Goals and Milestones

## Goals

Goals are high-level outcomes. They represent what you want to achieve, not how you will get there. The "how" lives in projects and tasks.

### Database Fields

**Table:** `goals`

| Field            | Type          | Constraints                             | Purpose                          |
|------------------|---------------|-----------------------------------------|----------------------------------|
| id               | bigIncrements | PK                                      | Primary key                      |
| user_id          | foreignId     | FK to users                             | Tenant owner                     |
| life_area_id     | foreignId     | FK to life_areas                        | Which life area this serves      |
| title            | string        | required                                | Goal name                        |
| description      | text          | nullable                                | Detailed description             |
| why              | text          | nullable                                | Motivation -- surfaced in planning |
| horizon          | enum          | short, medium, long                     | Time horizon                     |
| status           | enum          | active, completed, paused, abandoned    | Current state                    |
| target_date      | date          | nullable                                | Completion target                |
| progress_percent | integer       | default: 0                              | Auto-calculated from tasks       |
| created_at       | timestamp     |                                         |                                  |
| updated_at       | timestamp     |                                         |                                  |

### Relationships

| Relation       | Type    | Target        |
|----------------|---------|---------------|
| lifeArea       | BelongsTo | LifeArea    |
| milestones     | HasMany | Milestone     |
| tasks          | HasMany | Task          |
| projects       | HasMany | Project       |
| aiInteractions | HasMany | AiInteraction |

### Filament Resource

**Navigation:** Goals & Projects > Goals

**Form Fields:**
- Title (required)
- Life Area (select)
- Linked Goal (optional select)
- Description (textarea)
- Why (textarea -- motivation)
- Horizon (select: short/medium/long)
- Status (select: active/completed/paused/abandoned)
- Target Date (date picker)

**Table Columns:**
- Title, Life Area (badge), Horizon, Status (color-coded badge), Target Date, Progress %

**Filters:**
- Life Area, Status, Horizon

### Best Practices

1. **Always write a "why"** -- Without motivation, goals become chores
2. **Set a horizon** -- This determines how the AI evaluates urgency
3. **Link to a Life Area** -- Every goal must belong to exactly one area
4. **Review weekly** -- Use the Weekly Review to assess progress

---

## Milestones

Milestones are checkpoints within a goal. They break a long goal into reviewable phases.

### Database Fields

**Table:** `milestones`

| Field      | Type          | Constraints       | Purpose              |
|------------|---------------|-------------------|----------------------|
| id         | bigIncrements | PK                | Primary key          |
| goal_id    | foreignId     | FK to goals       | Parent goal          |
| title      | string        | required          | Milestone name       |
| due_date   | date          | nullable          | Target date          |
| status     | enum          | pending, in_progress, completed | Current state |
| sort_order | smallInteger  | default: 0        | Display ordering     |
| created_at | timestamp     |                   |                      |
| updated_at | timestamp     |                   |                      |

### Relationships

| Relation | Type      | Target |
|----------|-----------|--------|
| goal     | BelongsTo | Goal   |
| tasks    | HasMany   | Task   |

### When to Use Milestones

- Goals longer than 30 days benefit from milestones
- Use milestones to mark phase transitions (e.g., "Design complete", "Beta launch")
- Tasks can be linked to milestones for finer progress tracking
- The Streak Highlights widget celebrates recently completed milestones
