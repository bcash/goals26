# 10. Meetings

The meeting intelligence system captures client meetings, extracts scope decisions, tracks delivered value, and detects resource signals. It turns conversations into structured data that feeds the deferral pipeline and opportunity tracking.

## ClientMeeting Model

### Database Fields

**Table:** `client_meetings`

| Field                | Type          | Constraints                              | Purpose                               |
|----------------------|---------------|------------------------------------------|---------------------------------------|
| id                   | bigIncrements | PK                                       | Primary key                           |
| user_id              | foreignId     | FK to users                              | Tenant owner                          |
| project_id           | foreignId     | nullable, FK to projects                 | Associated project                    |
| title                | string        | required                                 | Meeting title                         |
| meeting_date         | date          |                                          | When the meeting occurred             |
| meeting_type         | string        |                                          | Category of meeting                   |
| client_type          | enum          | external, internal/self                  | External client or internal/self      |
| attendees            | json          | array                                    | List of attendee names                |
| transcript           | text          | nullable                                 | Full meeting transcript               |
| summary              | text          | nullable                                 | Meeting summary                       |
| decisions            | text          | nullable                                 | Key decisions made                    |
| action_items         | text          | nullable                                 | Action items from the meeting         |
| ai_scope_analysis    | text          | nullable                                 | AI-generated scope analysis           |
| source               | string        | nullable                                 | Where the meeting data came from      |
| granola_meeting_id   | string        | nullable                                 | External ID from Granola integration  |
| transcription_status | string        | pending, processing, complete            | Current transcription state           |
| transcript_received_at | datetime    | nullable                                 | When the transcript was received      |
| analysis_completed_at  | datetime    | nullable                                 | When AI analysis finished             |
| created_at           | timestamp     |                                          |                                       |
| updated_at           | timestamp     |                                          |                                       |

### Relationships

| Relation              | Type    | Target               |
|-----------------------|---------|----------------------|
| project               | BelongsTo | Project            |
| scopeItems            | HasMany | MeetingScopeItem     |
| doneItems             | HasMany | MeetingDoneItem      |
| resourceSignals       | HasMany | MeetingResourceSignal|
| agenda                | HasOne  | MeetingAgenda        |

### Helper Methods

| Method            | Returns  | Purpose                                         |
|-------------------|----------|-------------------------------------------------|
| `isSelfMeeting()` | boolean | True if client_type is internal/self            |
| `isAnalyzed()`    | boolean | True if analysis_completed_at is not null       |
| `clientLabel()`   | string  | Human-readable label for the client type        |
| `inScopeItems()`  | Collection | Scope items with type "in-scope"             |
| `outOfScopeItems()` | Collection | Scope items with type "out-of-scope"       |
| `risks()`         | Collection | Scope items with type "risk"                 |

## MeetingScopeItem Model

Scope items capture what was agreed as in-scope, explicitly out-of-scope, or identified as a risk during a meeting.

### Database Fields

**Table:** `meeting_scope_items`

| Field                 | Type          | Constraints                | Purpose                                  |
|-----------------------|---------------|----------------------------|------------------------------------------|
| id                    | bigIncrements | PK                         | Primary key                              |
| user_id               | foreignId     | FK to users                | Tenant owner                             |
| meeting_id            | foreignId     | FK to client_meetings      | Parent meeting                           |
| task_id               | foreignId     | nullable, FK to tasks      | Linked task (if applicable)              |
| description           | text          |                            | What was scoped in, out, or flagged      |
| type                  | string        | in-scope, out-of-scope, risk | Classification                        |
| confirmed_with_client | boolean       |                            | Whether the client explicitly agreed     |
| client_quote          | text          | nullable                   | Direct quote from the client             |
| notes                 | text          | nullable                   | Additional context                       |

### Relationships

| Relation      | Type      | Target        |
|---------------|-----------|---------------|
| clientMeeting | BelongsTo | ClientMeeting |
| task          | BelongsTo | Task          |

## MeetingDoneItem Model

Done items record what was delivered and demonstrated during a meeting, including client reactions and value metrics.

### Database Fields

**Table:** `meeting_done_items`

| Field               | Type          | Constraints                | Purpose                                 |
|---------------------|---------------|----------------------------|-----------------------------------------|
| id                  | bigIncrements | PK                         | Primary key                             |
| user_id             | foreignId     | FK to users                | Tenant owner                            |
| meeting_id          | foreignId     | FK to client_meetings      | Parent meeting                          |
| task_id             | foreignId     | nullable, FK to tasks      | Linked task                             |
| project_id          | foreignId     | nullable, FK to projects   | Linked project                          |
| title               | string        |                            | What was delivered                       |
| description         | text          | nullable                   | Detailed description                    |
| outcome             | text          | nullable                   | Result or impact of the delivery        |
| outcome_metric      | string        | nullable                   | Measurable outcome indicator            |
| client_reaction     | text          | nullable                   | How the client responded                |
| client_quote        | text          | nullable                   | Direct quote from the client            |
| value_delivered     | decimal       | nullable                   | Dollar value of the delivery            |
| save_as_testimonial | boolean       |                            | Flag for testimonial collection         |
| save_for_portfolio  | boolean       |                            | Flag for portfolio inclusion            |
| save_for_case_study | boolean       |                            | Flag for case study material            |

### Relationships

| Relation | Type      | Target        |
|----------|-----------|---------------|
| meeting  | BelongsTo | ClientMeeting |
| task     | BelongsTo | Task          |
| project  | BelongsTo | Project       |

## MeetingResourceSignal Model

Resource signals capture moments when a client mentions needing additional resources, tools, or capabilities -- signals that may create future opportunities.

### Database Fields

**Table:** `meeting_resource_signals`

| Field                       | Type          | Constraints                     | Purpose                                  |
|-----------------------------|---------------|---------------------------------|------------------------------------------|
| id                          | bigIncrements | PK                              | Primary key                              |
| user_id                     | foreignId     | FK to users                     | Tenant owner                             |
| meeting_id                  | foreignId     | FK to client_meetings           | Parent meeting                           |
| deferred_item_id            | foreignId     | nullable, FK to deferred_items  | Linked deferred item (if created)        |
| resource_type               | string        |                                 | Type of resource needed                  |
| description                 | text          |                                 | What the client needs                    |
| client_quote                | text          | nullable                        | Direct quote from the client             |
| constraint_timeline         | string        | nullable                        | When they need it by                     |
| creates_revisit_opportunity | boolean       |                                 | Whether this signals a future sale       |

### Relationships

| Relation     | Type      | Target        |
|--------------|-----------|---------------|
| clientMeeting| BelongsTo | ClientMeeting |
| deferredItem | BelongsTo | DeferredItem  |

## Filament Resource

**Navigation:** Meetings

### Form Layout

The meeting form uses a tabbed layout with three tabs:

**Tab 1: Meeting Details**
- Client Type (select: external, internal/self)
- Project (select, nullable)
- Title (required)
- Meeting Date (date picker)
- Meeting Type (text input)
- Transcription Status (select: pending, processing, complete)
- Attendees (JSON array input)
- Source (text input)
- Granola Meeting ID (text input)

**Tab 2: Transcript**
- Transcript (textarea)
- Summary (textarea)
- Decisions (textarea)

**Tab 3: Scope & Actions**
- AI Scope Analysis (read-only text, populated by AI)
- Action Items (textarea)

### Table Columns

| Column              | Format                                    |
|---------------------|-------------------------------------------|
| Project             | Project name                              |
| Title               | Meeting title                             |
| Meeting Type        | Badge                                     |
| Client Type         | Badge                                     |
| Meeting Date        | Date                                      |
| Source              | Badge                                     |
| Transcription Status| Badge with color (pending/processing/complete) |

**Default sort:** `meeting_date` descending (most recent first)

### Filters

- Meeting Type
- Client Type
- Project

### Relation Managers

- **ScopeItemsRelationManager** -- Manage in-scope, out-of-scope, and risk items extracted from the meeting
- **DoneItemsRelationManager** -- Track what was delivered and demonstrated, with value metrics and client reactions
- **ResourceSignalsRelationManager** -- Capture resource needs and revisit opportunities detected during the meeting

### Pages

- List, Create, View, Edit

## AiService Meeting Methods

### analyzeMeetingScope(ClientMeeting)

Processes a meeting transcript and extracts structured scope intelligence:

- **In-scope items** -- Work that was explicitly agreed upon or confirmed
- **Out-of-scope items** -- Work that was explicitly deferred or declined
- **Risks** -- Potential issues or concerns raised during the meeting
- **Action items** -- Concrete next steps with owners

The analysis populates the `ai_scope_analysis` field on the meeting and creates individual `MeetingScopeItem` records for each extracted item. Once complete, `analysis_completed_at` is set.

## DoneDeliveredWidget

**Dashboard position:** Sort 8, 1 column span

The Done/Delivered widget provides a snapshot of recent delivery activity:

- **Recent deliveries** -- The five most recent `MeetingDoneItem` records with titles and meeting context
- **Total value delivered** -- Sum of `value_delivered` across all done items
- **Tasks done this month** -- Count of tasks completed in the current calendar month

## Meeting Intelligence Workflow

1. **Create a meeting record** before or after the meeting with basic details (title, date, type, client type, project).

2. **Add the transcript.** This can come from manual entry, the Granola integration (via `granola_meeting_id`), or another transcription source. The `transcription_status` tracks the pipeline state.

3. **Run AI analysis.** Once a transcript is available, the `analyzeMeetingScope` method extracts scope items, risks, and action items automatically.

4. **Review scope items.** After AI analysis, review the extracted scope items in the Scope Items relation manager. Confirm or adjust the classification (in-scope, out-of-scope, risk). Link scope items to existing tasks where applicable.

5. **Record deliveries.** Use the Done Items relation manager to document what was delivered during the meeting. Capture client reactions, quotes, and value metrics. Flag items for testimonials, portfolio, or case studies.

6. **Capture resource signals.** When clients mention needing additional resources or capabilities, record these as resource signals. Items that create revisit opportunities can flow into the deferral pipeline.

7. **Follow up.** Action items from the meeting become tasks. Out-of-scope items and resource signals feed the deferral pipeline for future opportunity tracking.
