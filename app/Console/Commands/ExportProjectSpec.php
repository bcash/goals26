<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Services\SpecExportService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ExportProjectSpec extends Command
{
    protected $signature = 'spec:export
                            {project_id : The project ID to export}
                            {--output= : Custom output directory}';

    protected $description = 'Export a project\'s task tree as a set of markdown specification files';

    public function handle(SpecExportService $service): int
    {
        $project = Project::withoutGlobalScopes()->find($this->argument('project_id'));

        if (!$project) {
            $this->error("Project #{$this->argument('project_id')} not found.");

            return self::FAILURE;
        }

        $outputDir = $this->option('output')
            ?? storage_path('app/specs/' . Str::slug($project->name) . '-' . now()->format('Y-m-d'));

        $this->info("Exporting spec for: {$project->name}");
        $this->newLine();

        $result = $service->export($project, $outputDir);

        $this->info("Export complete!");
        $this->newLine();
        $this->table(
            ['Metric', 'Value'],
            [
                ['Output Directory', $result['output_dir']],
                ['Files Generated', $result['file_count']],
                ['Total Size', number_format($result['total_size'] / 1024, 1) . ' KB'],
            ]
        );

        $this->newLine();
        $this->info('Files:');
        foreach ($result['files'] as $filename => $size) {
            $sizeKb = number_format($size / 1024, 1);
            $this->line("  {$filename} ({$sizeKb} KB)");
        }

        return self::SUCCESS;
    }
}
