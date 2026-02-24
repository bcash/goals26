# Solas Rún User Manual

> Reference documentation for every feature, field, and workflow in Solas Rún.

## Chapters

| # | Chapter | Description |
|---|---------|-------------|
| 01 | [Life Areas](./01-life-areas.md) | The six foundational categories |
| 02 | [Goals and Milestones](./02-goals-and-milestones.md) | Setting and tracking long-term outcomes |
| 03 | [Projects](./03-projects.md) | Managing initiatives with scope and deadlines |
| 04 | [Task Tree](./04-task-tree.md) | Hierarchical task decomposition and management |
| 05 | [Spec Export](./05-spec-export.md) | Exporting task specifications as markdown |
| 06 | [Daily Planning](./06-daily-planning.md) | Morning and evening sessions, time blocks |
| 07 | [Habits](./07-habits.md) | Building and tracking recurring behaviours |
| 08 | [Journal](./08-journal.md) | Daily reflection and mood tracking |
| 09 | [Weekly Reviews](./09-weekly-reviews.md) | Structured end-of-week assessments |
| 10 | [Meetings](./10-meetings.md) | Client meetings, agendas, and intelligence extraction |
| 11 | [Deferral Pipeline](./11-deferral-pipeline.md) | Deferred items, reviews, and opportunities |
| 12 | [Budget and Time](./12-budget-and-time.md) | Project budgets and time entry tracking |
| 13 | [AI Features](./13-ai-features.md) | AI integration points and interactions |
| 14 | [Dashboard](./14-dashboard.md) | The Daily Command Center and its 9 widgets |
| 15 | [MCP Server](./15-mcp-server.md) | AI agent access via the Model Context Protocol |
| 16 | [Administration](./16-administration.md) | Multi-tenancy, CLI commands, and configuration |

## Conventions Used

- **Field tables** list every database field with its type, constraints, and purpose
- **Filament UI** sections describe the admin panel forms and table columns
- **CLI** sections document Artisan commands and MCP tool interfaces
- All money values are stored as **decimal dollars** (not cents)
- All dates use **YYYY-MM-DD** format in the database (Carbon casts in PHP)
- PostgreSQL-specific: `ilike` for case-insensitive search, `array_position()` for enum ordering
