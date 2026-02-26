<?php

namespace App\Console\Commands;

use App\Models\ServerMigrationApp;
use App\Services\ServerMigrationService;
use Illuminate\Console\Command;

class MigrationClone extends Command
{
    protected $signature = 'migration:clone
                            {--app= : Clone a specific app by cloudways_app_id or app_label}
                            {--batch=5 : Number of concurrent clone operations}
                            {--resume : Resume any stuck cloning operations}
                            {--dry-run : Show what would be cloned without doing it}';

    protected $description = 'Clone WordPress apps from source to target Cloudways server';

    public function handle(ServerMigrationService $service): int
    {
        if (! config('services.cloudways.api_key')) {
            $this->error('Cloudways API key not configured. Set CLOUDWAYS_API_KEY in .env');

            return self::FAILURE;
        }

        if (! config('services.cloudways.target_server_id')) {
            $this->error('Target server not configured. Set CLOUDWAYS_TARGET_SERVER_ID in .env');

            return self::FAILURE;
        }

        // Resume mode
        if ($this->option('resume')) {
            return $this->handleResume($service);
        }

        // Single app mode
        if ($this->option('app')) {
            return $this->handleSingleApp($service);
        }

        // Batch mode
        return $this->handleBatch($service);
    }

    private function handleResume(ServerMigrationService $service): int
    {
        $this->info('Resuming stuck clone operations...');

        $count = $service->resumeCloning(
            fn (string $msg) => $this->line($msg)
        );

        $this->info("  → {$count} operations resumed and completed.");

        return self::SUCCESS;
    }

    private function handleSingleApp(ServerMigrationService $service): int
    {
        $identifier = $this->option('app');
        $app = ServerMigrationApp::where('cloudways_app_id', $identifier)
            ->orWhere('app_label', $identifier)
            ->first();

        if (! $app) {
            $this->error("App not found: {$identifier}");

            return self::FAILURE;
        }

        if (! $app->canClone()) {
            $this->warn("App '{$app->app_label}' is in status '{$app->status}' and cannot be cloned.");
            $this->comment('Set status to "pending" in the database to retry.');

            return self::FAILURE;
        }

        $isDryRun = (bool) $this->option('dry-run');

        if ($isDryRun) {
            $this->info("[DRY RUN] Would clone: {$app->app_label} ({$app->cloudways_app_id})");
            $this->info("  Domain: {$app->primary_domain}");
            $this->info('  Source: '.config('services.cloudways.source_server_id'));
            $this->info('  Target: '.config('services.cloudways.target_server_id'));

            return self::SUCCESS;
        }

        if (! $this->confirm("Clone '{$app->app_label}' to target server?")) {
            return self::SUCCESS;
        }

        $this->info("Cloning {$app->app_label}...");

        $result = $service->cloneApp($app, fn (string $msg) => $this->line($msg));

        if ($result->status === 'cloned') {
            $this->info("Clone complete: {$app->app_label}");
        } else {
            $this->error("Clone failed: {$result->last_error}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function handleBatch(ServerMigrationService $service): int
    {
        $batchSize = (int) $this->option('batch');
        $isDryRun = (bool) $this->option('dry-run');
        $pending = ServerMigrationApp::migratable()->byStatus('pending')->count();

        if ($pending === 0) {
            $this->info('No pending apps to clone.');
            $summary = $service->getSummary();
            $this->comment("  Cloned: {$summary['cloned']} | Failed: {$summary['failed']} | Completed: {$summary['completed']}");

            return self::SUCCESS;
        }

        $this->info("{$pending} apps pending clone.");

        if ($isDryRun) {
            $apps = ServerMigrationApp::migratable()->byStatus('pending')->orderBy('app_label')->get();
            $this->info('[DRY RUN] Would clone in batches of '.$batchSize.':');

            foreach ($apps as $app) {
                $this->line("  • {$app->app_label} ({$app->primary_domain})");
            }

            return self::SUCCESS;
        }

        if (! $this->confirm("Clone {$pending} apps in batches of {$batchSize}?")) {
            return self::SUCCESS;
        }

        $this->info("Starting batch clone (batch size: {$batchSize})...");
        $this->newLine();

        $stats = $service->cloneBatch(
            batchSize: $batchSize,
            onProgress: fn (string $msg) => $this->line($msg)
        );

        $this->newLine();
        $this->info('Batch clone complete:');
        $this->info("  Cloned: {$stats['cloned']}");
        $this->info("  Failed: {$stats['failed']}");
        $this->info("  Skipped: {$stats['skipped']}");

        if ($stats['failed'] > 0) {
            $this->warn('Some apps failed. Run `php artisan migration:status --failed` to see details.');
        }

        return self::SUCCESS;
    }
}
