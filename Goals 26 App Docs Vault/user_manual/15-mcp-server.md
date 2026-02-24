# 15. MCP Server

The solas-run MCP server provides Claude Code with full read/write access to all 25 Eloquent models in the application. It uses a generic, data-driven architecture where 3 tool/resource classes are dynamically instantiated per model, producing 53 tools and 25 resource templates from a single registry.

## Architecture

| Component | File | Purpose |
|-----------|------|---------|
| Server | `app/Mcp/Servers/SolasRunServer.php` | MCP server entry point (version 1.0.0) |
| Transport | STDIO via `laravel/mcp` v0.5.9 | Communication layer for Claude Code |
| ListModels | `app/Mcp/Tools/ListModels.php` | Generic query tool (instantiated per slug) |
| InspectModel | `app/Mcp/Tools/InspectModel.php` | Generic schema introspection tool (instantiated per slug) |
| ModelRecord | `app/Mcp/Resources/ModelRecord.php` | Generic resource template (instantiated per slug) |
| UpdateTaskPlan | `app/Mcp/Tools/UpdateTaskPlan.php` | Save implementation plan to a task |
| UpdateTaskContext | `app/Mcp/Tools/UpdateTaskContext.php` | Save working context to a task |
| ExportProjectSpec | `app/Mcp/Tools/ExportProjectSpec.php` | Export project task tree as markdown specs |
| ModelRegistry | `app/Mcp/Support/ModelRegistry.php` | Configuration for all 25 models |
| ModelResolver | `app/Mcp/Support/ModelResolver.php` | Query building, schema, relationship discovery |

## Tool Inventory (53 Total)

### Inspect Tools (25)

One `inspect-{slug}` tool per model. Zero arguments. Returns the full schema introspection for the model:

- `slug` -- Model identifier
- `label` -- Human-readable name
- `table` -- Database table name
- `fillable` -- Mass-assignable fields
- `casts` -- Attribute cast definitions
- `hidden` -- Hidden attributes
- `relationships` -- Discovered BelongsTo, HasMany, HasOne, BelongsToMany
- `filters` -- Available enum filters with allowed values
- `searchable` -- Text fields available for ilike search
- `dates` -- Date and datetime fields
- `has_tenant` -- Whether the model uses HasTenant scoping

### List Tools (25)

One `list-{slug}` tool per model. Returns paginated, filtered, searchable query results with all BelongsTo relationships eager-loaded.

**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| search | string | Full-text search across all searchable fields (PostgreSQL ilike) |
| page | integer | Page number (default: 1) |
| per_page | integer | Results per page (max: 100) |
| order_by | string | Column to sort by (default: created_at) |
| order_dir | string | Sort direction: asc or desc (default: desc) |
| {filter_name} | string | Enum filter (allowed values defined in registry) |
| {date_field}_from | string | Date range start in YYYY-MM-DD |
| {date_field}_to | string | Date range end in YYYY-MM-DD |

### Custom Tools (3)

**update-task-plan**

Saves an implementation plan (markdown) to a task's `plan` field.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| task_id | integer | Yes | The task ID |
| plan | string | Yes | Markdown content for the plan |

**update-task-context**

Saves working context (markdown) to a task's `context` field.

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| task_id | integer | Yes | The task ID |
| context | string | Yes | Markdown content for the context |

**export-project-spec**

Exports a project's task tree as markdown specification files (see [Chapter 05](./05-spec-export.md)).

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| project_id | integer | Yes | The project ID |
| output_dir | string | No | Custom output directory |

## Resource Templates (25)

One `ModelRecord` resource template per model.

**URI pattern:** `{slug}://solas-run/{id}`

Each resource returns the full record as JSON with all BelongsTo relationships eager-loaded.

## ModelRegistry

**File:** `app/Mcp/Support/ModelRegistry.php`

The registry defines configuration for all 25 models. Each entry specifies:

| Key | Type | Description |
|-----|------|-------------|
| class | string | Fully qualified Eloquent model class |
| label | string | Human-readable name |
| filters | array | Enum fields with allowed values (e.g., status, priority) |
| searchable | array | Text fields for ilike search |
| dates | array | Date/datetime fields for range filtering |
| belongsTo | array | Relationships to eager-load |
| hasTenant | boolean | Whether the model uses HasTenant user scoping |

### All 25 Model Slugs

| Slug | Label |
|------|-------|
| user | User |
| life-area | Life Area |
| goal | Goal |
| project | Project |
| task | Task |
| habit | Habit |
| habit-log | Habit Log |
| daily-plan | Daily Plan |
| time-block | Time Block |
| journal-entry | Journal Entry |
| weekly-review | Weekly Review |
| ai-interaction | AI Interaction |
| client-meeting | Client Meeting |
| meeting-scope-item | Meeting Scope Item |
| meeting-done-item | Meeting Done Item |
| meeting-resource-signal | Meeting Resource Signal |
| meeting-agenda | Meeting Agenda |
| agenda-item | Agenda Item |
| task-quality-gate | Task Quality Gate |
| deferred-item | Deferred Item |
| deferral-review | Deferral Review |
| opportunity-pipeline | Opportunity Pipeline |
| project-budget | Project Budget |
| time-entry | Time Entry |
| milestone | Milestone |

## ModelResolver

**File:** `app/Mcp/Support/ModelResolver.php`

The resolver provides the query and introspection layer used by all generic tools and resources.

| Method | Signature | Description |
|--------|-----------|-------------|
| query | `query(string $slug): Builder` | Creates a query builder for the model, bypasses tenant scopes via `withoutGlobalScopes()` |
| find | `find(string $slug, int $id): Model` | Finds a single record with all BelongsTo relationships eager-loaded |
| schema | `schema(string $slug): array` | Returns full schema introspection (fillable, casts, hidden, relationships, filters, etc.) |
| discoverRelationships | `discoverRelationships(Model $model): array` | Uses PHP reflection to discover BelongsTo, HasMany, HasOne, BelongsToMany relationships |

The `query()` method calls `withoutGlobalScopes()` because the MCP server runs in a STDIO context with no authenticated user, so tenant scoping must be bypassed.

## Server Registration

The `SolasRunServer::boot()` method registers all tools and resources in two phases:

**Dynamic registration:**

Loops through `ModelRegistry::slugs()` and for each slug creates:
1. A `ListModels` tool instance (`list-{slug}`)
2. An `InspectModel` tool instance (`inspect-{slug}`)
3. A `ModelRecord` resource template (`{slug}://solas-run/{id}`)

**Static registration:**

Registers the three custom tools:
1. `UpdateTaskPlan`
2. `UpdateTaskContext`
3. `ExportProjectSpec`

**Server instructions:**

The server's `#[McpServer]` attribute includes instructions that communicate conventions to the AI agent:

- Money fields store decimal dollars (not cents)
- Dates use YYYY-MM-DD format
- Use PostgreSQL `ilike` for case-insensitive search
- Priority ordering: critical > high > medium > low
- Task statuses: todo, in-progress, done, deferred, blocked
- Models with the HasTenant trait are user-scoped; the MCP server bypasses this via `withoutGlobalScopes()`

## Usage Examples

**Inspect a model's schema:**
```
Tool: inspect-task
Args: (none)
```

**List tasks filtered by status and priority:**
```
Tool: list-task
Args: { "status": "in-progress", "priority": "high", "per_page": 20 }
```

**Search projects by name:**
```
Tool: list-project
Args: { "search": "solas" }
```

**Filter by date range:**
```
Tool: list-daily-plan
Args: { "plan_date_from": "2026-02-01", "plan_date_to": "2026-02-28" }
```

**Read a single record:**
```
Resource: task://solas-run/42
```

**Save task plan and context:**
```
Tool: update-task-plan
Args: { "task_id": 42, "plan": "## Step 1\n- Build the component\n\n## Step 2\n- Wire up events" }

Tool: update-task-context
Args: { "task_id": 42, "context": "## Current State\nComponent scaffolded. Events pending." }
```
