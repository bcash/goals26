<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use App\Services\SpecExportService;
use App\Services\TaskTreeService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ExportProjectSpec extends Tool
{
    protected string $name = 'export-project-spec';

    protected string $description = 'Export a project\'s task tree as a set of markdown specification files (.md) for bootstrapping a new Claude Code project. Generates CLAUDE.md, SPECIFICATION.md, individual task specs, and a CHECKLIST.md.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()
                ->description('The ID of the project to export')
                ->required(),
            'output_dir' => $schema->string()
                ->description('Custom output directory path. Defaults to storage/app/specs/{project-slug}-{date}')
                ->nullable(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'project_id' => 'required|integer|exists:projects,id',
            'output_dir' => 'nullable|string',
        ]);

        $project = Project::withoutGlobalScopes()->findOrFail($validated['project_id']);

        $outputDir = $validated['output_dir']
            ?? storage_path('app/specs/' . Str::slug($project->name) . '-' . now()->format('Y-m-d'));

        $service = new SpecExportService(new TaskTreeService());
        $result = $service->export($project, $outputDir);

        return Response::json([
            'success' => true,
            'project' => $project->name,
            'output_dir' => $result['output_dir'],
            'file_count' => $result['file_count'],
            'total_size' => $result['total_size'],
            'files' => array_keys($result['files']),
        ]);
    }
}
