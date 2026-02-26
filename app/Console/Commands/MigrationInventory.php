<?php

namespace App\Console\Commands;

use App\Models\ServerMigrationApp;
use App\Services\ServerMigrationService;
use Illuminate\Console\Command;

class MigrationInventory extends Command
{
    protected $signature = 'migration:inventory
                            {--refresh : Re-fetch from Cloudways API even if records exist}
                            {--show-inactive : Include inactive/skip apps in output}';

    protected $description = 'Fetch all apps from source Cloudways server and populate tracking table';

    public function handle(ServerMigrationService $service): int
    {
        if (! config('services.cloudways.api_key')) {
            $this->error('Cloudways API key not configured. Set CLOUDWAYS_API_KEY in .env');

            return self::FAILURE;
        }

        $existingCount = ServerMigrationApp::count();

        if ($existingCount > 0 && ! $this->option('refresh')) {
            $this->info("Tracking table already has {$existingCount} records.");
            $this->comment('Use --refresh to re-fetch from Cloudways API.');
            $this->displaySummary($service);

            return self::SUCCESS;
        }

        $this->info('Fetching apps from Cloudways source server...');

        $records = $service->inventory(
            fn (int $current, int $total, string $label) => $this->output->write("\r  → {$current}/{$total}: {$label}")
        );

        $this->newLine();

        if ($records->isEmpty()) {
            $this->warn('No apps found on source server.');

            return self::FAILURE;
        }

        $this->info("  → {$records->count()} apps synced to tracking table");
        $this->newLine();

        $this->displaySummary($service);
        $this->displayAppsTable();

        return self::SUCCESS;
    }

    private function displaySummary(ServerMigrationService $service): void
    {
        $summary = $service->getSummary();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Total apps', $summary['total']],
                ['To migrate', $summary['migratable']],
                ['Pending', $summary['pending']],
                ['Cloning', $summary['cloning']],
                ['Cloned', $summary['cloned']],
                ['DNS switched', $summary['dns_switched']],
                ['Verified', $summary['verified']],
                ['Completed', $summary['completed']],
                ['Failed', $summary['failed']],
            ]
        );
    }

    private function displayAppsTable(): void
    {
        $query = ServerMigrationApp::query()->orderBy('app_label');

        if (! $this->option('show-inactive')) {
            $query->migratable();
        }

        $apps = $query->get();

        $rows = $apps->map(fn (ServerMigrationApp $app) => [
            $app->cloudways_app_id,
            $app->app_label,
            $app->primary_domain ?? '-',
            $app->category,
            $app->should_migrate ? 'Yes' : 'No',
            $app->status,
        ]);

        $this->table(
            ['CW ID', 'Label', 'Domain', 'Category', 'Migrate?', 'Status'],
            $rows->toArray()
        );
    }
}
