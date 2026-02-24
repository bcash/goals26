# 08. Journal

The Journal provides a space for structured reflection. Entries capture thoughts, moods, and insights across four entry types. Unlike the DailyPlan's evening reflection (which is tightly coupled to task progress), journal entries are open-ended and support longer-form writing.

## Purpose

Journaling closes the feedback loop between planning and learning. Morning entries set intention. Evening entries process the day. Weekly entries zoom out. Freeform entries capture anything that does not fit the other types. Over time, the journal becomes a searchable record of your inner landscape, and the mood field enables trend analysis.

---

## JournalEntry

### Database Fields

**Table:** `journal_entries`

| Field       | Type          | Constraints                               | Purpose                              |
|-------------|---------------|-------------------------------------------|--------------------------------------|
| id          | bigIncrements | PK                                        | Primary key                          |
| user_id     | foreignId     | FK to users                               | Tenant owner                         |
| entry_date  | date          | required                                  | Which day this entry covers          |
| entry_type  | enum          | morning, evening, weekly, freeform        | Category of entry                    |
| content     | text          | required                                  | The journal entry body (markdown)    |
| mood        | integer       | nullable, 1--5                            | Self-rated mood                      |
| tags        | json          | nullable                                  | Array of string tags for categorization |
| ai_insights | text          | nullable                                  | AI-generated reflections or patterns |
| created_at  | timestamp     |                                           |                                      |
| updated_at  | timestamp     |                                           |                                      |

### Relationships

The JournalEntry model has no relationships beyond the standard `belongsTo User` (via tenant scoping). Journal entries are intentionally standalone -- they are not linked to tasks, goals, or daily plans.

---

## Entry Types

Each entry type serves a different reflective purpose:

| Type     | Purpose                                                                                              |
|----------|------------------------------------------------------------------------------------------------------|
| morning  | Set the tone for the day. Capture how you feel on waking, what you are looking forward to, and what you want to focus on. Best written before checking email or starting work. |
| evening  | Process the day. Reflect on what went well, what was difficult, and what you learned. Complements the DailyPlan's structured evening ratings with open narrative. |
| weekly   | Zoom out to the week level. Review patterns, celebrate wins, identify recurring blockers, and set intentions for the coming week. Best written on Sunday evening or Monday morning. |
| freeform | Catch-all for thoughts that do not fit a time-based cadence. Ideas, frustrations, breakthroughs, gratitude lists, or anything else worth capturing. |

---

## Mood Tracking

The mood field uses a 1--5 integer scale:

| Value | Label        | Interpretation                         |
|-------|--------------|----------------------------------------|
| 1     | Very Low     | Struggling, depleted, overwhelmed      |
| 2     | Low          | Below baseline, tired or frustrated    |
| 3     | Neutral      | Baseline, functional, unremarkable     |
| 4     | Good         | Energized, motivated, positive         |
| 5     | Excellent    | Peak state, flow, deep satisfaction    |

Mood is optional on every entry. When populated consistently, it enables trend analysis over time -- for example, correlating mood dips with specific life areas or workload patterns.

---

## Filament Resource

**Navigation:** Journal > Journal (sort 1)

### Form Fields

| Field       | Type            | Details                                                                 |
|-------------|-----------------|-------------------------------------------------------------------------|
| entry_date  | Date picker     | Required, defaults to today                                             |
| entry_type  | Select          | Required, defaults to freeform; options: morning, evening, weekly, freeform |
| mood        | Select          | 1--5 with labels (Very Low, Low, Neutral, Good, Excellent)             |
| content     | MarkdownEditor  | Limited toolbar: bold, italic, lists, heading, blockquote, link         |
| tags        | TagsInput       | Comma-separated string tags                                            |
| ai_insights | Placeholder     | AI-generated content, not directly editable                             |

### Table Columns

| Column     | Display                                                         |
|------------|-----------------------------------------------------------------|
| entry_date | Bold, sortable                                                  |
| entry_type | Color-coded badge (morning=info, evening=warning, weekly=success, freeform=gray) |
| mood       | Formatted 1--5 value                                            |
| content    | Truncated to 80 characters                                      |

**Default sort:** `entry_date` descending.

### Filter

- **entry_type** -- Filter by morning, evening, weekly, or freeform

### Actions

| Action | Behavior                    |
|--------|-----------------------------|
| View   | Opens read-only detail view |
| Edit   | Opens the edit form         |
| Delete | Deletes the entry           |

---

## Tags

Tags are stored as a JSON array of strings on the `tags` field. They provide a lightweight categorization system independent of life areas or goals. Common tag patterns include:

- Emotional states: `gratitude`, `frustration`, `clarity`
- Topics: `health`, `career`, `relationships`, `creativity`
- Processes: `weekly-review`, `planning`, `retrospective`

Tags are entered via a TagsInput component and are comma-separated. There is no predefined tag list -- tags are freeform and user-defined.

---

## AI Insights

The `ai_insights` field stores AI-generated reflections based on the journal entry content. This field is a placeholder in the form and is populated by the AI service when available. Insights might include:

- Pattern recognition across recent entries
- Mood trend observations
- Connections between journal themes and active goals
- Suggestions for reflection prompts

The field is read-only in the UI and is generated asynchronously after the entry is saved.
