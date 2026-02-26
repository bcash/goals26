<?php

namespace App\Mcp\Servers;

use App\Mcp\Resources\ModelRecord;
use App\Mcp\Support\ModelRegistry;
use App\Mcp\Tools\ExportProjectSpec;
use App\Mcp\Tools\ImportGranolaMeeting;
use App\Mcp\Tools\InspectModel;
use App\Mcp\Tools\ListModels;
use App\Mcp\Tools\QueryGranolaMeetings;
use App\Mcp\Tools\UpdateTaskContext;
use App\Mcp\Tools\UpdateTaskPlan;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Solas Rún')]
#[Version('1.0.0')]
#[Instructions(<<<'MARKDOWN'
This MCP server provides full read/write access to the Solas Rún personal operating system database.

For each of the 26 Eloquent models you get:
- `inspect-{slug}`: Returns schema introspection (table, fillable, casts, relationships, available filters)
- `list-{slug}`: Query records with filters, date ranges, full-text search (ilike), and pagination
- `{slug}://solas-run/{id}`: Fetch a single record by ID with BelongsTo relationships loaded

Task memory tools (for session persistence):
- `update-task-plan`: Save implementation plan to a task's `plan` field
- `update-task-context`: Save working context to a task's `context` field

Spec export:
- `export-project-spec`: Export a project's task tree as markdown spec files for a new Claude Code project

Granola integration (requires user to have connected Granola via OAuth):
- `query-granola-meetings`: Search or list meetings from the user's Granola account
- `import-granola-meeting`: Import a specific Granola meeting as a ClientMeeting record with AI analysis

Key conventions:
- Legacy money fields store decimal dollars — e.g., `estimated_value` = 5000.00
- New money fields (budget_cents, amount_cents) store integer cents — e.g., `budget_cents` = 500000 for $5,000.00
- Dates use Carbon (date or datetime casts) — pass YYYY-MM-DD for filters
- PostgreSQL: search uses case-insensitive `ilike`
- Priority ordering: critical > high > medium > low
- Task statuses: todo, in-progress, done, deferred, blocked
- Models with HasTenant trait are user-scoped (tenant bypass enabled for local MCP)

Available model slugs:
user, life-area, goal, project, task, habit, habit-log, daily-plan, time-block,
journal-entry, weekly-review, ai-interaction, client-meeting, meeting-scope-item,
meeting-done-item, meeting-resource-signal, meeting-agenda, agenda-item,
task-quality-gate, deferred-item, deferral-review, opportunity-pipeline,
project-budget, time-entry, cost-entry, milestone
MARKDOWN)]
class SolasRunServer extends Server
{
    /**
     * Dynamically register tools and resources for all 26 models.
     */
    protected function boot(): void
    {
        foreach (ModelRegistry::all() as $slug => $config) {
            // inspect-{slug}: Schema introspection tool
            $this->tools[] = new InspectModel($slug, $config);

            // list-{slug}: Query/filter/search/paginate tool
            $this->tools[] = new ListModels($slug, $config);

            // {slug}://solas-run/{id}: Single record resource template
            $this->resources[] = new ModelRecord($slug, $config);
        }

        // Task memory tools — persist plan and context across sessions
        $this->tools[] = new UpdateTaskPlan;
        $this->tools[] = new UpdateTaskContext;

        // Spec export tool — generate markdown specs for new projects
        $this->tools[] = new ExportProjectSpec;

        // Granola integration tools — query and import meetings
        $this->tools[] = new QueryGranolaMeetings;
        $this->tools[] = new ImportGranolaMeeting;
    }
}
