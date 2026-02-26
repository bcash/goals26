<?php

namespace App\Console\Commands;

use App\Models\ServerMigrationApp;
use App\Services\ServerMigrationService;
use Illuminate\Console\Command;

class MigrationVerify extends Command
{
    protected $signature = 'migration:verify
                            {--app= : Verify a specific app by cloudways_app_id or app_label}
                            {--timeout=15 : HTTP request timeout in seconds}
                            {--all : Verify all migratable apps regardless of status}';

    protected $description = 'Verify migrated sites are loading correctly on the new server';

    public function handle(ServerMigrationService $service): int
    {
        $timeout = (int) $this->option('timeout');

        // Single app mode
        if ($this->option('app')) {
            return $this->handleSingleApp($service, $timeout);
        }

        // Batch mode
        return $this->handleBatch($service, $timeout);
    }

    private function handleSingleApp(ServerMigrationService $service, int $timeout): int
    {
        $identifier = $this->option('app');
        $app = ServerMigrationApp::where('cloudways_app_id', $identifier)
            ->orWhere('app_label', $identifier)
            ->first();

        if (! $app) {
            $this->error("App not found: {$identifier}");

            return self::FAILURE;
        }

        $this->info("Verifying {$app->app_label} ({$app->primary_domain})...");

        $result = $service->verify($app, $timeout, fn (string $msg) => $this->line($msg));

        if ($result->verified) {
            $this->info('Verification passed.');
        } else {
            $this->error("Verification failed: {$result->verification_notes}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function handleBatch(ServerMigrationService $service, int $timeout): int
    {
        $apps = $this->option('all')
            ? ServerMigrationApp::migratable()->whereNotNull('primary_domain')->get()
            : ServerMigrationApp::migratable()
                ->whereIn('status', ['dns_switched', 'verified', 'failed'])
                ->whereNotNull('primary_domain')
                ->get();

        if ($apps->isEmpty()) {
            $this->info('No apps ready for verification.');
            $this->comment('Apps must be in dns_switched status. Run `php artisan migration:dns` first.');

            return self::SUCCESS;
        }

        $this->info("Verifying {$apps->count()} apps...");
        $this->newLine();

        $stats = $service->verifyBatch(
            apps: $apps,
            timeout: $timeout,
            onProgress: fn (string $msg) => $this->line($msg)
        );

        $this->newLine();
        $this->info('Verification complete:');
        $this->info("  Passed: {$stats['verified']}");
        $this->info("  Failed: {$stats['failed']}");

        if ($stats['failed'] > 0) {
            $this->warn('Some apps failed verification. Run `php artisan migration:status --failed` for details.');
        }

        return self::SUCCESS;
    }
}
