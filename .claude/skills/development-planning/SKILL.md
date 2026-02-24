---
name: development-planning
description: "Structured planning for multi-file features with task context persistence. Activates when entering plan mode, starting a complex feature, discussing architecture, breaking down tasks, or when the user mentions plan, architect, design, breakdown, decompose, strategy, or approach."
---

# Development Planning — Structured Feature Implementation

## When to Apply

Activate this skill when:

- Entering plan mode for a new feature
- Breaking down a complex task into subtasks
- Making architectural decisions
- Starting work on a multi-day or multi-session feature
- Resuming work on a previously started feature

## Planning Workflow

### Step 1: Research the Codebase

Before proposing any plan:

1. **Search for similar implementations** — Use Grep/Glob to find existing patterns
2. **Read related service classes** — Check `app/Services/` for existing business logic
3. **Check model relationships** — Understand the data model involved
4. **Review Filament resources** — See how the admin UI handles related entities
5. **Inspect the MCP registry** — Run `inspect-{model}` for schema details

### Step 2: Document the Plan

For any feature touching 3+ files, create a structured plan:

```markdown
## Feature: [Name]

### Context
- What problem does this solve?
- What existing patterns should we follow?
- What are the constraints?

### Files to Create/Modify
1. Migration: `database/migrations/...`
2. Model: `app/Models/...`
3. Service: `app/Services/...`
4. Resource: `app/Filament/Resources/...`
5. Tests: `tests/Feature/...`

### Implementation Steps
1. [ ] Create migration and run it
2. [ ] Update model with fillable, casts, relationships
3. [ ] Create/update service with business logic
4. [ ] Create/update Filament resource
5. [ ] Write tests
6. [ ] Run Pint and verify

### Edge Cases
- What happens when...?
- How does this interact with...?

### Open Questions
- Should we...?
- What's the preference for...?
```

### Step 3: Persist Task Context

After planning, ALWAYS save context to the task's memory fields via MCP:

1. **Use `update-task-plan`** to save the implementation plan
2. **Use `update-task-context`** to save key files, decisions, and requirements
3. This ensures any future session can pick up where you left off

Example plan content:
```
## Implementation Plan for "Add Meeting Transcription"

### Approach
- Use GranolaSyncService to poll for new transcripts
- Create a queued job for processing
- Store raw transcript in client_meetings.transcript
- Run AI analysis via AiService after transcript received

### Key Files
- app/Services/GranolaSyncService.php (modify)
- app/Jobs/ProcessMeetingTranscript.php (create)
- app/Services/MeetingIntelligenceService.php (modify)
- database/migrations/xxx_add_transcript_fields.php (create)

### Decisions Made
- Use polling (not webhooks) because Granola doesn't support webhooks yet
- Store raw transcript as TEXT, not JSON — it's unstructured
- AI analysis runs async via queue to avoid blocking
```

Example context content:
```
## Key Context for "Add Meeting Transcription"

### Related Models
- ClientMeeting: has transcript, transcription_status, transcript_received_at
- MeetingScopeItem: extracted scope items from analysis
- MeetingDoneItem: extracted deliverables from analysis

### Service Dependencies
- GranolaSyncService: handles Granola API communication
- MeetingIntelligenceService: runs AI analysis on transcripts
- AiService: orchestrates LLM calls

### Requirements
- Transcripts can be very large (50k+ chars)
- Analysis must extract: scope items, done items, resource signals
- Must handle partial transcripts gracefully
- Status flow: pending → processing → completed/failed
```

## Resuming Previous Work

When the user says "continue working on X" or "resume the [feature] task":

1. Use `list-task` with `search` parameter to find the task
2. Read the task via `task://solas-run/{id}` to get plan and context fields
3. Review the plan and context to understand where you left off
4. Continue from the next uncompleted step

## Architecture Decision Guidelines

### When to Create a New Service
- Business logic that spans multiple models
- Logic that's reused across Filament resources and API endpoints
- Complex workflows (multi-step processes)

### When to Modify an Existing Service
- The logic belongs to the same domain
- The new feature is an extension of existing behavior
- The service is already responsible for that model

### When to Create a New Model
- The data has its own lifecycle (created/updated independently)
- It needs its own Filament resource for management
- It has relationships to multiple other models

### When to Add Columns to an Existing Model
- The data is 1:1 with the existing model
- It has no independent lifecycle
- It would be awkward to query through a join
- PostgreSQL TOAST handles large TEXT values efficiently

## Common Pitfalls in Planning

- Don't plan too many files at once — break into phases
- Don't skip the research step — you'll miss existing patterns
- Don't forget to plan for tests alongside implementation
- Don't assume the happy path — plan for error handling
- Don't forget tenant isolation — most models need user_id scoping
- Remember to save task context via MCP before ending the session
