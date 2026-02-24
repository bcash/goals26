<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SpecExportService
{
    /**
     * Mapping of task IDs to their numbered filename prefixes.
     * Built during tree traversal for cross-referencing.
     */
    protected array $taskNumberMap = [];

    /**
     * Flat collection of all leaf tasks for checklist generation.
     */
    protected Collection $leafTasks;

    public function __construct(
        protected TaskTreeService $taskTreeService,
    ) {
        $this->leafTasks = collect();
    }

    /**
     * Export a project's task tree as a set of markdown spec files.
     *
     * @return array{output_dir: string, files: array<string, int>, total_size: int}
     */
    public function export(Project $project, string $outputDir): array
    {
        // Ensure output directories exist
        File::ensureDirectoryExists($outputDir);
        File::ensureDirectoryExists($outputDir . '/specs');

        // Load the full task tree for this project
        $tree = $this->taskTreeService->getTree(projectId: $project->id);

        // First pass: assign numbers to all tasks
        $this->assignNumbers($tree);

        // Generate all files
        $files = [];

        // 1. CLAUDE.md — AI agent instructions
        $files['CLAUDE.md'] = $this->generateClaudeMd($project, $outputDir);

        // 2. SPECIFICATION.md — Project overview and WBS
        $files['SPECIFICATION.md'] = $this->generateSpecificationMd($project, $tree, $outputDir);

        // 3. Individual task spec files
        $this->generateTaskSpecs($tree, $outputDir, $files);

        // 4. CHECKLIST.md — Flat checklist of all leaf tasks
        $files['CHECKLIST.md'] = $this->generateChecklistMd($outputDir);

        $totalSize = array_sum($files);

        return [
            'output_dir' => $outputDir,
            'files' => $files,
            'file_count' => count($files),
            'total_size' => $totalSize,
        ];
    }

    /**
     * First pass: assign hierarchical numbers to all tasks in the tree.
     * Root tasks get 01, 02, 03...
     * Children get 01a, 01b, 01c...
     * Grandchildren get 01a-i, 01a-ii...
     */
    protected function assignNumbers(Collection $tasks, string $parentNumber = ''): void
    {
        foreach ($tasks->values() as $index => $task) {
            $number = $this->buildNumber($index, $parentNumber, $task->depth ?? 0);
            $slug = Str::slug($task->title);
            $slug = Str::limit($slug, 50, '');

            $filename = "{$number}-{$slug}.md";
            $this->taskNumberMap[$task->id] = [
                'number' => $number,
                'filename' => $filename,
                'title' => $task->title,
            ];

            // Track leaf tasks for checklist
            if ($task->is_leaf || !$task->relationLoaded('children') || $task->children->isEmpty()) {
                $this->leafTasks->push($task);
            }

            // Recurse into children
            if ($task->relationLoaded('children') && $task->children->isNotEmpty()) {
                $this->assignNumbers($task->children, $number);
            }
        }
    }

    /**
     * Build a hierarchical number string based on depth and position.
     */
    protected function buildNumber(int $index, string $parentNumber, int $depth): string
    {
        if ($depth === 0 || $parentNumber === '') {
            // Root tasks: 01, 02, 03...
            return str_pad($index + 1, 2, '0', STR_PAD_LEFT);
        }

        if ($depth === 1) {
            // First-level children: 01a, 01b, 01c...
            return $parentNumber . chr(97 + $index); // a, b, c...
        }

        // Deeper nesting: 01a-i, 01a-ii, 01a-iii...
        $roman = $this->toRoman($index + 1);

        return $parentNumber . '-' . $roman;
    }

    /**
     * Convert integer to lowercase roman numeral.
     */
    protected function toRoman(int $number): string
    {
        $map = [
            10 => 'x', 9 => 'ix', 5 => 'v', 4 => 'iv', 1 => 'i',
        ];
        $result = '';
        foreach ($map as $value => $numeral) {
            while ($number >= $value) {
                $result .= $numeral;
                $number -= $value;
            }
        }

        return $result;
    }

    /**
     * Generate CLAUDE.md — Instructions for the implementing team's AI agent.
     */
    protected function generateClaudeMd(Project $project, string $outputDir): int
    {
        $lines = [];
        $lines[] = "# {$project->name}";
        $lines[] = '';
        $lines[] = '## Project Overview';
        $lines[] = '';

        if ($project->description) {
            $lines[] = $project->description;
            $lines[] = '';
        }

        if ($project->client_name) {
            $lines[] = "**Client:** {$project->client_name}";
            $lines[] = '';
        }

        if ($project->tech_stack) {
            $lines[] = '## Tech Stack';
            $lines[] = '';
            $lines[] = $project->tech_stack;
            $lines[] = '';
        }

        if ($project->architecture_notes) {
            $lines[] = '## Architecture';
            $lines[] = '';
            $lines[] = $project->architecture_notes;
            $lines[] = '';
        }

        // Custom template content
        if ($project->export_template) {
            $lines[] = $project->export_template;
            $lines[] = '';
        }

        $lines[] = '## Working with This Specification';
        $lines[] = '';
        $lines[] = '- Read `SPECIFICATION.md` for the full project overview and work breakdown structure';
        $lines[] = '- Individual task specs are in the `specs/` directory, numbered hierarchically';
        $lines[] = '- Use `CHECKLIST.md` to track implementation progress';
        $lines[] = '- Each spec file contains acceptance criteria, technical requirements, and dependencies';
        $lines[] = '- Complete leaf tasks (actionable items) and work up the tree';
        $lines[] = '';

        $content = implode("\n", $lines);
        File::put($outputDir . '/CLAUDE.md', $content);

        return strlen($content);
    }

    /**
     * Generate SPECIFICATION.md — Project overview with WBS summary table.
     */
    protected function generateSpecificationMd(Project $project, Collection $tree, string $outputDir): int
    {
        $lines = [];
        $lines[] = "# Specification: {$project->name}";
        $lines[] = '';
        $lines[] = '## Project Details';
        $lines[] = '';

        if ($project->description) {
            $lines[] = $project->description;
            $lines[] = '';
        }

        $lines[] = "| Field | Value |";
        $lines[] = '|---|---|';
        $lines[] = "| Status | {$project->status} |";
        if ($project->client_name) {
            $lines[] = "| Client | {$project->client_name} |";
        }
        if ($project->due_date) {
            $lines[] = "| Due Date | {$project->due_date->format('Y-m-d')} |";
        }
        $lines[] = "| Generated | " . now()->format('Y-m-d H:i') . ' |';
        $lines[] = '';

        if ($project->tech_stack) {
            $lines[] = '## Tech Stack';
            $lines[] = '';
            $lines[] = $project->tech_stack;
            $lines[] = '';
        }

        if ($project->architecture_notes) {
            $lines[] = '## Architecture';
            $lines[] = '';
            $lines[] = $project->architecture_notes;
            $lines[] = '';
        }

        // WBS Summary Table
        $lines[] = '## Work Breakdown Structure';
        $lines[] = '';
        $lines[] = '| # | Task | Priority | Status | Subtasks | Spec |';
        $lines[] = '|---|---|---|---|---|---|';

        $this->buildWbsTable($tree, $lines);

        $lines[] = '';

        // Dependencies section
        $dependencyTasks = collect($this->taskNumberMap)->filter(function ($info) {
            $taskId = array_search($info, $this->taskNumberMap);
            $task = Task::withoutGlobalScopes()->find($taskId);

            return $task && $task->dependencies_description;
        });

        if ($dependencyTasks->isNotEmpty()) {
            $lines[] = '## Dependencies';
            $lines[] = '';
            foreach ($dependencyTasks as $taskId => $info) {
                $task = Task::withoutGlobalScopes()->find($taskId);
                $lines[] = "### {$info['number']}. {$info['title']}";
                $lines[] = '';
                $lines[] = $task->dependencies_description;
                $lines[] = '';
            }
        }

        $content = implode("\n", $lines);
        File::put($outputDir . '/SPECIFICATION.md', $content);

        return strlen($content);
    }

    /**
     * Build WBS table rows recursively.
     */
    protected function buildWbsTable(Collection $tasks, array &$lines, string $indent = ''): void
    {
        foreach ($tasks as $task) {
            $info = $this->taskNumberMap[$task->id] ?? null;
            if (!$info) {
                continue;
            }

            $hasChildren = $task->relationLoaded('children') && $task->children->isNotEmpty();
            $subtasks = $hasChildren ? 'Yes' : 'No';
            $priority = ucfirst($task->priority ?? 'medium');
            $status = ucfirst(str_replace('-', ' ', $task->status ?? 'todo'));

            $lines[] = "| {$info['number']} | {$indent}{$task->title} | {$priority} | {$status} | {$subtasks} | [spec](specs/{$info['filename']}) |";

            if ($hasChildren) {
                $this->buildWbsTable($task->children, $lines, $indent . '&nbsp;&nbsp;');
            }
        }
    }

    /**
     * Generate individual task spec files recursively.
     */
    protected function generateTaskSpecs(Collection $tasks, string $outputDir, array &$files): void
    {
        foreach ($tasks as $task) {
            $info = $this->taskNumberMap[$task->id] ?? null;
            if (!$info) {
                continue;
            }

            $content = $this->generateSingleTaskSpec($task, $info);
            $filepath = "specs/{$info['filename']}";
            File::put($outputDir . '/' . $filepath, $content);
            $files[$filepath] = strlen($content);

            // Recurse into children
            if ($task->relationLoaded('children') && $task->children->isNotEmpty()) {
                $this->generateTaskSpecs($task->children, $outputDir, $files);
            }
        }
    }

    /**
     * Generate a single task spec markdown file.
     */
    protected function generateSingleTaskSpec(Task $task, array $info): string
    {
        $lines = [];
        $lines[] = "# {$info['number']}. {$task->title}";
        $lines[] = '';

        // Meta table
        $lines[] = '| Field | Value |';
        $lines[] = '|---|---|';
        $lines[] = '| Priority | ' . ucfirst($task->priority ?? 'medium') . ' |';
        $lines[] = '| Status | ' . ucfirst(str_replace('-', ' ', $task->status ?? 'todo')) . ' |';
        if ($task->due_date) {
            $lines[] = "| Due Date | {$task->due_date->format('Y-m-d')} |";
        }
        if ($task->time_estimate_minutes) {
            $lines[] = "| Estimate | {$task->time_estimate_minutes} minutes |";
        }
        $lines[] = '';

        // Description (notes field)
        if ($task->notes) {
            $lines[] = '## Description';
            $lines[] = '';
            $lines[] = $task->notes;
            $lines[] = '';
        }

        // Implementation Plan
        if ($task->plan) {
            $lines[] = '## Implementation Plan';
            $lines[] = '';
            $lines[] = $task->plan;
            $lines[] = '';
        }

        // Acceptance Criteria
        if ($task->acceptance_criteria) {
            $lines[] = '## Acceptance Criteria';
            $lines[] = '';
            $lines[] = $task->acceptance_criteria;
            $lines[] = '';
        }

        // Technical Requirements
        if ($task->technical_requirements) {
            $lines[] = '## Technical Requirements';
            $lines[] = '';
            $lines[] = $task->technical_requirements;
            $lines[] = '';
        }

        // Dependencies
        if ($task->dependencies_description) {
            $lines[] = '## Dependencies';
            $lines[] = '';
            $lines[] = $task->dependencies_description;
            $lines[] = '';
        }

        // Subtasks
        $hasChildren = $task->relationLoaded('children') && $task->children->isNotEmpty();
        if ($hasChildren) {
            $lines[] = '## Subtasks';
            $lines[] = '';
            foreach ($task->children as $child) {
                $childInfo = $this->taskNumberMap[$child->id] ?? null;
                if ($childInfo) {
                    $status = $child->status === 'done' ? 'x' : ' ';
                    $lines[] = "- [{$status}] **{$childInfo['number']}. {$child->title}** — [spec]({$childInfo['filename']}) [{$child->priority}]";
                }
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    /**
     * Generate CHECKLIST.md — Flat checklist of all leaf tasks.
     */
    protected function generateChecklistMd(string $outputDir): int
    {
        $lines = [];
        $lines[] = '# Implementation Checklist';
        $lines[] = '';
        $lines[] = 'All actionable leaf tasks. Complete these to finish the project.';
        $lines[] = '';

        // Group by priority for easy scanning
        $grouped = $this->leafTasks->groupBy(fn ($task) => $task->priority ?? 'medium');

        $priorityOrder = ['critical', 'high', 'medium', 'low'];

        foreach ($priorityOrder as $priority) {
            $tasks = $grouped->get($priority, collect());
            if ($tasks->isEmpty()) {
                continue;
            }

            $lines[] = "## " . ucfirst($priority) . " Priority";
            $lines[] = '';

            foreach ($tasks as $task) {
                $info = $this->taskNumberMap[$task->id] ?? null;
                if (!$info) {
                    continue;
                }

                $status = $task->status === 'done' ? 'x' : ' ';
                $lines[] = "- [{$status}] {$info['number']}. {$task->title} — [spec](specs/{$info['filename']})";
            }

            $lines[] = '';
        }

        $content = implode("\n", $lines);
        File::put($outputDir . '/CHECKLIST.md', $content);

        return strlen($content);
    }
}
