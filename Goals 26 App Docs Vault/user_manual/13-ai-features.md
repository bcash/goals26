# Chapter 13: AI Features

## Overview

Solas Run integrates AI across eight distinct touchpoints, all managed through a central `AiService`. Every AI call is logged as an `AiInteraction` record for auditability and review. The system currently returns simulated responses, with the architecture in place to swap in a live LLM provider.

---

## AiService

**Path:** `app/Services/AiService.php`

The `AiService` is the central hub for all AI integration. Its methods are grouped by domain.

### Morning / Evening Methods

| Method | Signature | Description |
|---|---|---|
| `generateMorningIntention` | `generateMorningIntention(DailyPlan $plan)` | Creates an inspiring 2-3 sentence morning intention based on the day's plan, theme, and priorities. |
| `generateEveningSummary` | `generateEveningSummary(DailyPlan $plan)` | Reflects on the day's accomplishments and patterns, summarizing what was completed and noting recurring themes. |

### Planning Methods

| Method | Signature | Description |
|---|---|---|
| `generateGoalBreakdown` | `generateGoalBreakdown(Goal $goal)` | Suggests 3-5 milestones for a goal, including first actions, required resources, and dependencies between milestones. |
| `generateDailyPlan` | `generateDailyPlan(User $user)` | Creates a full daily plan including theme, priorities, and time blocks based on the user's goals, habits, and upcoming commitments. |
| `generateWeeklyAnalysis` | `generateWeeklyAnalysis(WeeklyReview $review)` | Produces a 3-4 paragraph strategic analysis of the week's patterns, identifying trends in productivity, goal progress, and energy levels. |

### Opportunity Analysis Methods

| Method | Signature | Description |
|---|---|---|
| `analyzeMeetingScope` | `analyzeMeetingScope(ClientMeeting $meeting)` | Extracts scope intelligence from meeting transcripts, identifying deliverables, constraints, and potential scope changes. |
| `analyzeOpportunity` | `analyzeOpportunity(DeferredItem $item)` | Writes an opportunity brief for items tagged as commercial, assessing market fit, effort, and potential value. |
| `analyzePersonalGoal` | `analyzePersonalGoal(DeferredItem $item)` | Analyzes personal goals with attention to resource constraints, time requirements, and alignment with life areas. |

### General Methods

| Method | Signature | Description |
|---|---|---|
| `freeformChat` | `freeformChat(string $message, ?string $context = null)` | Generic AI chat. Accepts a message and optional context string for open-ended interactions. |
| `generateGoalBrainstorm` | `generateGoalBrainstorm(LifeArea $area)` | Generates 3-5 fresh goal ideas for a given life area, considering existing goals to avoid duplication. |
| `assessResourceReadiness` | `assessResourceReadiness(Project $project)` | Evaluates a project's resource risks, checking for gaps in skills, budget, timeline, and dependencies. |

### Core Infrastructure Methods

| Method | Signature | Description |
|---|---|---|
| `chat` | `chat(string $type, string $prompt, array $context = [])` | Wrapper method that delegates to `callAi`. Accepts an interaction type, prompt, and optional context array. |
| `callAi` | `callAi(string $type, string $prompt, array $context = [])` | Central execution method. Logs all interactions as `AiInteraction` records. Currently routes to `simulateResponse()` instead of a live provider. |
| `simulateResponse` | `simulateResponse()` | Returns realistic simulated responses keyed by interaction type. Used during development and when no AI provider is configured. |

### Call Flow

All public AI methods ultimately call through the same path:

```
Public method (e.g., generateMorningIntention)
  -> chat(type, prompt, context)
    -> callAi(type, prompt, context)
      -> simulateResponse()  [current implementation]
      -> Logs AiInteraction record
```

---

## AiInteraction Model

**Table:** `ai_interactions`

### Fields

| Column | Type | Constraints | Description |
|---|---|---|---|
| `id` | bigint | PK, auto-increment | Primary key |
| `user_id` | bigint unsigned | FK to `users.id` | User who initiated the interaction |
| `interaction_type` | string | | Type identifier (see values below) |
| `context_json` | json | nullable | Structured context data sent with the prompt |
| `prompt` | text | | The full prompt sent to the AI |
| `response` | text | | The AI's response |
| `daily_plan_id` | bigint unsigned | FK to `daily_plans.id`, nullable | Associated daily plan, if applicable |
| `goal_id` | bigint unsigned | FK to `goals.id`, nullable | Associated goal, if applicable |
| `tokens_used` | integer | nullable | Token count for the interaction |
| `model_used` | string | nullable | Identifier of the AI model used |
| `created_at` | timestamp | | |
| `updated_at` | timestamp | | |

### Interaction Types

| Type Value | Triggered By |
|---|---|
| `daily-morning` | Morning intention generation |
| `daily-evening` | Evening summary generation |
| `weekly` | Weekly analysis |
| `goal-breakdown` | Goal breakdown suggestions |
| `freeform` | Freeform chat interactions |

### Relationships

- `belongsTo` **DailyPlan** via `daily_plan_id`
- `belongsTo` **Goal** via `goal_id`

---

## AiInteraction Filament Resource

**Navigation Group:** AI
**Navigation Label:** AI Interactions

This is a **read-only** resource. The `canCreate` method returns `false`; interactions are created programmatically by the `AiService`.

### Table Columns

| Column | Display | Notes |
|---|---|---|
| Datetime | Formatted | Format: `M j, g:i A` (e.g., "Feb 23, 9:15 AM") |
| Interaction Type | Badge | Color-coded by type |
| Model | Text | The model identifier used |
| Tokens | Numeric | Right-aligned |
| Prompt | Text | Truncated to 60 characters |

Default sort: `created_at` descending.

### Filters

- **Interaction Type**: Filter by `daily-morning`, `daily-evening`, `weekly`, `goal-breakdown`, `freeform`.

### Actions

- **View**: Opens a read-only detail view of the full interaction record. This is the only available action (no edit, no delete from the table).

---

## AI Integration Points

The eight places where AI is invoked across the application:

### 1. Morning Intention Generation

**Widget:** `AiIntentionWidget` (Dashboard)
**Service Method:** `AiService::generateMorningIntention()`

The dashboard widget displays the morning intention for today's daily plan. If no intention exists yet, a "Generate" button calls the service method. Includes a fallback message if the AI call fails. Shows plan status alongside the intention text.

### 2. Evening Summary Generation

**Trigger:** DailyPlan evening session
**Service Method:** `AiService::generateEveningSummary()`

When a user begins their evening review of a daily plan, the system generates a reflective summary of the day's accomplishments, noting patterns in what was completed and what was deferred.

### 3. Goal Breakdown Suggestions

**Service Method:** `AiService::generateGoalBreakdown()`

Produces 3-5 suggested milestones for a goal, each with recommended first actions, resource requirements, and dependency relationships. Used to bootstrap a goal's milestone structure.

### 4. Daily Plan Generation

**Trigger:** `DailyPlanService::buildFromAi()`
**Service Method:** `AiService::generateDailyPlan()`

Generates a complete daily plan including a theme, prioritized tasks, and suggested time blocks. The `DailyPlanService` orchestrates the creation, calling the AI service for content and then persisting the structured result.

### 5. Weekly Analysis

**Trigger:** WeeklyReview
**Service Method:** `AiService::generateWeeklyAnalysis()`

Produces a 3-4 paragraph strategic analysis accompanying a weekly review, identifying productivity patterns, goal trajectory, and energy trends across the week.

### 6. Meeting Scope Analysis

**Trigger:** ClientMeeting transcript processing
**Service Method:** `AiService::analyzeMeetingScope()`

When a client meeting has a transcript attached, this analysis extracts scope intelligence: deliverables discussed, constraints mentioned, timeline implications, and potential scope changes to watch for.

### 7. Opportunity Analysis

**Trigger:** DeferredItem processing
**Service Method:** `AiService::analyzeOpportunity()` and `AiService::analyzePersonalGoal()`

For deferred items flagged as commercial opportunities, `analyzeOpportunity()` generates an opportunity brief. For personal items, `analyzePersonalGoal()` evaluates resource constraints and life-area alignment.

### 8. Quality Gate Checklist Generation

**Service:** `QualityGateService`
**Service Method:** Uses AI to generate review checklists

When a parent task's children are all completed, the quality gate process generates an AI-powered review checklist. See the next section for details.

---

## QualityGateService AI Integration

**Path:** `app/Services/QualityGateService.php`

The `QualityGateService` bridges task completion with AI-driven quality review.

### Methods

| Method | Signature | Description |
|---|---|---|
| `trigger` | `trigger(Task $task)` | Called when all child tasks of a parent are marked done. Generates an AI checklist, creates a `TaskQualityGate` record, and sends a notification to the user. |
| `generateChecklist` | `generateChecklist(Task $task)` | Uses AI to generate 3-6 review questions as a JSON array. The questions are tailored to the task's title, description, and child task content. |
| `defaultChecklist` | `defaultChecklist()` | Returns a static fallback checklist when the AI is unavailable or the call fails. Ensures quality gates always have questions to answer. |
| `submitReview` | `submitReview(TaskQualityGate $gate, string $status, ?string $notes)` | Records the review decision on the gate. If the review **passes**, the result propagates upward to parent tasks (triggering their own quality gates if applicable). If the review **fails**, child tasks are reopened for further work. |

### Flow

```
All child tasks marked done
  -> trigger(parentTask)
    -> generateChecklist(parentTask)
      -> AI generates 3-6 review questions (JSON)
      -> Falls back to defaultChecklist() on failure
    -> Creates TaskQualityGate record
    -> Sends notification to user

User reviews checklist
  -> submitReview(gate, 'passed', notes)
    -> Propagates upward to parent quality gates
  -> submitReview(gate, 'failed', notes)
    -> Reopens child tasks for rework
```
