# 03. Projects

Projects are concrete initiatives with a defined scope that contribute toward a goal. They contain the task tree that breaks work into actionable items.

## Database Fields

**Table:** `projects`

| Field              | Type          | Constraints                          | Purpose                            |
|--------------------|---------------|--------------------------------------|------------------------------------|
| id                 | bigIncrements | PK                                   | Primary key                        |
| user_id            | foreignId     | FK to users                          | Tenant owner                       |
| life_area_id       | foreignId     | FK to life_areas                     | Which life area this serves        |
| goal_id            | foreignId     | nullable, FK to goals                | Optional linked goal               |
| name               | string        | required                             | Project name                       |
| description        | text          | nullable                             | Detailed description               |
| tech_stack         | text          | nullable                             | Languages, frameworks, versions    |
| architecture_notes | text          | nullable                             | High-level architecture design     |
| export_template    | text          | nullable                             | Custom CLAUDE.md content for export|
| status             | enum          | active, on-hold, complete, archived  | Current state                      |
| client_name        | string        | nullable                             | Client name (blank = personal)     |
| due_date           | date          | nullable                             | Project deadline                   |
| color_hex          | string(7)     | nullable                             | Visual identification colour       |
| created_at         | timestamp     |                                      |                                    |
| updated_at         | timestamp     |                                      |                                    |

## Relationships

| Relation        | Type    | Target         |
|-----------------|---------|----------------|
| lifeArea        | BelongsTo | LifeArea     |
| goal            | BelongsTo | Goal         |
| tasks           | HasMany | Task           |
| clientMeetings  | HasMany | ClientMeeting  |
| budget          | HasOne  | ProjectBudget  |
| budgets         | HasMany | ProjectBudget  |
| timeEntries     | HasMany | TimeEntry      |

## Filament Resource

**Navigation:** Goals & Projects > Projects

### Form -- Project Details Section

- Name (required, max 255, full width)
- Life Area (required select)
- Linked Goal (optional select)
- Description (textarea, 3 rows)
- Status (select: active, on-hold, complete, archived)
- Client Name (text, placeholder: "Leave blank for personal projects")
- Due Date (date picker)
- Project Colour (color picker)

### Form -- Specification & Export Section (collapsible, collapsed by default)

- Tech Stack (textarea, 3 rows, placeholder: "e.g., Laravel 12, React 19, PostgreSQL 15, Tailwind CSS 4")
- Architecture Notes (textarea, 5 rows)
- Custom CLAUDE.md Template (textarea, 8 rows)

### Table Columns

- Color swatch, Name (searchable, bold), Life Area (badge), Client (gray placeholder "Personal"), Status (color-coded badge), Due Date

### Filters

- Life Area, Status

### Relation Managers

- **TasksRelationManager** -- View and manage tasks belonging to this project

### Pages

- List, Create, View, Edit

## Client vs. Personal Projects

Projects with a `client_name` are client projects. Projects without are personal. The table displays "Personal" in gray for projects without a client.

## Spec Export Fields

The three specification fields (tech_stack, architecture_notes, export_template) are used by the `SpecExportService` when generating markdown specs. They appear in the `CLAUDE.md` and `SPECIFICATION.md` files.

- **Tech Stack** -- Included under "Tech Stack" heading in both generated files
- **Architecture Notes** -- Included under "Architecture" heading
- **Export Template** -- Appended as custom content in CLAUDE.md
