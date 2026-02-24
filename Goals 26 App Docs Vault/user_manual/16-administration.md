# 16. Administration

Configuration, multi-tenancy, CLI commands, Claude Code hooks, and skills for the Solas Run application.

## Multi-Tenancy

### HasTenant Trait

20 of 25 models use the `HasTenant` trait, which adds automatic `user_id` scoping via a global scope. Any Eloquent query on a tenant-scoped model automatically filters to the authenticated user's records.

**Models without HasTenant (5):** User, Life Area, and three others that are system-level.

**Bypassing tenant scoping:**

Two contexts require bypassing the global scope:

1. **MCP server** -- Runs via STDIO transport with no authenticated user. The `ModelResolver::query()` method calls `withoutGlobalScopes()` on every query.
2. **CLI commands** -- Artisan commands run without an authenticated session. Commands use `withoutGlobalScopes()` where needed.

## CLI Commands

### spec:export

Exports a project's task tree as markdown specification files (see [Chapter 05](./05-spec-export.md) for full details on output format).

```bash
php artisan spec:export {project_id} {--output=}
```

| Argument | Required | Description |
|----------|----------|-------------|
| project_id | Yes | The project ID to export |
| --output | No | Custom output directory |

**Default output directory:** `storage/app/specs/{project-slug}-{date}/`

## Claude Code Hooks

Three shell script hooks in `.claude/hooks/` integrate Claude Code sessions with the Solas Run database.

### inject-task-context.sh

**Hook event:** SessionStart

Queries the database for up to 5 in-progress tasks that have `plan` or `context` fields populated. Uses `php artisan tinker` to execute the query.

**Injected output:** Formatted markdown block for each matching task showing:
- Task ID
- Task title
- Project name
- Status
- Plan content (if present)
- Context content (if present)

**Purpose:** Provides session continuity. When Claude Code starts a new session, it immediately has context about what the user was working on, including implementation plans and working notes from previous sessions.

### suggest-skills.sh

**Hook event:** UserPromptSubmit

Analyzes the user's prompt for keywords and suggests relevant Claude Code skills. The mapping:

| Skill | Trigger Keywords |
|-------|-----------------|
| laravel-filament-development | filament, resource, widget, dashboard, admin, relation manager, badge, filter, action, form, table, navigation, column |
| phpunit-testing | test, testing, phpunit, assert, factory, coverage, tdd, spec, verify |
| development-planning | plan, architect, design, breakdown, decompose, strategy, approach, implement feature, multi-file, complex, refactor |
| tailwindcss-development | css, style, tailwind, restyle, dark mode, responsive, layout, grid, flex, spacing, color, theme, ui, visual |

When a match is found, the hook outputs a suggestion for Claude Code to load the relevant skill file.

### remind-persist-context.sh

**Hook event:** Stop

Reminds Claude to save task plan and context before the session ends by calling the `update-task-plan` and `update-task-context` MCP tools. This ensures work-in-progress notes are persisted to the database for the next session.

## Claude Code Skills

Four skill files in `.claude/skills/` provide domain-specific guidance via YAML frontmatter and markdown content.

### laravel-filament-development

**File:** `.claude/skills/laravel-filament-development/SKILL.md`

Covers Filament v3 patterns used throughout Solas Run:

- **Project structure** -- Resource locations, page conventions, service layer boundaries
- **Resource patterns** -- Form builders, table builders, infolist builders
- **Widget patterns** -- All widgets extend `BaseWidget`; stat widgets, chart widgets, list widgets
- **Custom pages** -- Creating and registering custom Filament pages
- **Relation managers** -- Inline management of related records
- **Navigation conventions** -- Groups, icons, badge counts, sort ordering
- **Common pitfalls** -- Frequent mistakes and their solutions

### phpunit-testing

**File:** `.claude/skills/phpunit-testing/SKILL.md`

Documents the integration-first testing philosophy:

- **Preference** -- Feature tests preferred over unit tests
- **Test structure:**
  - `tests/Feature/Filament/` -- Filament resource and page tests
  - `tests/Feature/Services/` -- Service layer tests
  - `tests/Unit/` -- Isolated unit tests (used sparingly)
- **Factory usage** -- Model factories for test data setup
- **Database assertions** -- assertDatabaseHas, assertDatabaseCount, etc.
- **Filament testing** -- Testing resources with Livewire test helpers
- **Naming conventions** -- Test method naming patterns
- **RefreshDatabase trait** -- Required on all database-touching tests
- **PostgreSQL-specific notes** -- Enum handling, ilike in assertions, array columns

### development-planning

**File:** `.claude/skills/development-planning/SKILL.md`

Structured planning workflow for multi-file features:

1. **Research codebase** -- Inspect existing patterns, models, services
2. **Document plan** -- Write implementation plan with file-by-file breakdown
3. **Persist task context** -- Save plan and context to the task via MCP tools

Additional guidance:
- **Resuming previous work** -- Read task plan and context from MCP before starting
- **Architecture decisions** -- When to create a new service or model vs. modifying existing code
- **Common planning pitfalls** -- Over-engineering, missing edge cases, skipping research

### tailwindcss-development

**File:** `.claude/skills/tailwindcss-development/SKILL.md`

Covers Tailwind CSS v4 patterns:

- **CSS-first configuration** -- Uses `@theme` directive instead of `tailwind.config.js`
- **v4 import syntax** -- `@import "tailwindcss"` replaces older directives
- **Replaced utilities** -- Table of v3-to-v4 utility changes
- **Gap utilities** -- Use `gap-*` for spacing in flex and grid layouts
- **Dark mode** -- `dark:` variant for dark mode styling
- **Responsive design** -- Breakpoint prefixes and mobile-first approach

## Configuration

### Database

| Setting | Value |
|---------|-------|
| Engine | PostgreSQL 15+ |
| Port | 5468 |
| Database | solas_run |

### Server

| Setting | Value |
|---------|-------|
| Local server | Laravel Herd |
| Local domain | goals26.test |

### Framework Stack

| Package | Version |
|---------|---------|
| Laravel | 12 |
| Filament | v3 |
| laravel/mcp | v0.5.9 |
| laravel/boost | v2.2.0 |

### MCP Transport

The MCP server uses STDIO transport. Claude Code communicates with the server by spawning `php artisan mcp:serve` as a child process and exchanging JSON-RPC messages over standard input/output.
