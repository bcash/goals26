# 05. Spec Export

The Spec Export system generates markdown specification files from a project's task tree. These files bootstrap a new Claude Code project for an external development team.

## Use Case

1. You plan a project in Solas Rún, breaking it into a hierarchical task tree
2. You fill in acceptance criteria, technical requirements, and dependencies per task
3. You export the entire tree as markdown files
4. You hand the exported directory to another team or AI agent
5. That team implements from the spec independently
6. You manage their progress and report back to Solas Rún when done

## Generated File Structure

```
{output_dir}/
  CLAUDE.md                     -- AI agent instructions
  SPECIFICATION.md              -- Project overview and WBS table
  specs/
    01-task-title.md            -- Root tasks numbered sequentially
    02-another-task.md
    02a-child-task.md           -- Children use parent number + letter
    02b-another-child.md
    02a-i-grandchild.md         -- Grandchildren use roman numerals
    02a-ii-another-grandchild.md
  CHECKLIST.md                  -- Flat leaf-task checklist by priority
```

## Numbering Convention

| Depth | Pattern         | Example        |
|-------|-----------------|----------------|
| 0     | Two-digit       | 01, 02, 03     |
| 1     | Parent + letter | 01a, 01b, 02a  |
| 2+    | Parent + roman  | 01a-i, 01a-ii  |

Filenames use `Str::slug()` truncated to 50 characters for cross-platform safety.

## Generated File Contents

### CLAUDE.md

Contains:
- Project name and description
- Client name (if applicable)
- Tech stack (from `projects.tech_stack`)
- Architecture notes (from `projects.architecture_notes`)
- Custom template content (from `projects.export_template`)
- Instructions for working with the specification

### SPECIFICATION.md

Contains:
- Project details table (status, client, due date, generation timestamp)
- Tech stack and architecture sections
- **Work Breakdown Structure table** with columns: #, Task, Priority, Status, Subtasks (yes/no), Spec link
- Dependencies section (from tasks with `dependencies_description`)

### Task Spec Files (specs/*.md)

Each task generates a markdown file containing:
- Title with hierarchical number
- Meta table (priority, status, due date, time estimate)
- Description (from `notes` field)
- Implementation Plan (from `plan` field)
- Acceptance Criteria (from `acceptance_criteria` field)
- Technical Requirements (from `technical_requirements` field)
- Dependencies (from `dependencies_description` field)
- Subtasks list with links to child spec files

### CHECKLIST.md

Contains:
- All leaf tasks grouped by priority (critical, high, medium, low)
- Checkbox format: `- [ ] 02a. Task title -- [spec](specs/02a-task.md)`
- Done tasks show as checked: `- [x]`

## CLI Command

```bash
php artisan spec:export {project_id} --output=/path/to/dir
```

| Argument     | Required | Description                                     |
|--------------|----------|-------------------------------------------------|
| project_id   | Yes      | The project ID to export                        |
| --output     | No       | Custom output directory (default: storage/app/specs/{slug}-{date}) |

Output includes a summary table with file count and total size.

## MCP Tool

**Tool name:** `export-project-spec`

**Schema:**
- `project_id` (integer, required) -- The project ID
- `output_dir` (string, optional) -- Custom output directory

**Response:**
```json
{
  "success": true,
  "project": "Project Name",
  "output_dir": "/path/to/output",
  "file_count": 15,
  "total_size": 5388,
  "files": ["CLAUDE.md", "SPECIFICATION.md", "specs/01-task.md", ...]
}
```

## SpecExportService

**File:** `app/Services/SpecExportService.php`

**Constructor:** Depends on `TaskTreeService`

**Key Method:** `export(Project $project, string $outputDir): array`

**Internal workflow:**
1. Load the task tree via `TaskTreeService::getTree(projectId:)`
2. First pass: assign hierarchical numbers to all tasks
3. Generate CLAUDE.md from project fields
4. Generate SPECIFICATION.md with WBS table
5. Generate individual task spec files recursively
6. Generate CHECKLIST.md from leaf tasks grouped by priority
7. Return file count, total size, and file list

## Preparing for Export

For the best output, ensure these fields are populated:

**On the Project:**
- `tech_stack` -- What technologies will the implementing team use?
- `architecture_notes` -- How should the system be structured?
- `export_template` -- Any additional CLAUDE.md instructions?

**On each Task:**
- `notes` -- Task description and context
- `plan` -- Implementation approach (if known)
- `acceptance_criteria` -- What does "done" look like?
- `technical_requirements` -- Constraints and dependencies
- `dependencies_description` -- What must happen first?
