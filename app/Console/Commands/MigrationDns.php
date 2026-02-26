<?php

namespace App\Console\Commands;

use App\Models\ServerMigrationApp;
use App\Services\ServerMigrationService;
use Illuminate\Console\Command;

class MigrationDns extends Command
{
    protected $signature = 'migration:dns
                            {--app= : Switch DNS for a specific app by cloudways_app_id or app_label}
                            {--dry-run : Show DNS changes without applying them}
                            {--rollback : Rollback DNS to original IP for a specific app (requires --app)}';

    protected $description = 'Switch Cloudflare DNS records from old server IP to new server IP';

    public function handle(ServerMigrationService $service): int
    {
        if (! config('services.cloudflare.api_token')) {
            $this->error('Cloudflare API token not configured. Set CLOUDFLARE_API_TOKEN in .env');

            return self::FAILURE;
        }

        if (! config('services.cloudways.target_server_ip')) {
            $this->error('Target server IP not configured. Set CLOUDWAYS_TARGET_IP in .env');

            return self::FAILURE;
        }

        // Rollback mode
        if ($this->option('rollback')) {
            return $this->handleRollback($service);
        }

        // Single app mode
        if ($this->option('app')) {
            return $this->handleSingleApp($service);
        }

        // Batch mode
        return $this->handleBatch($service);
    }

    private function handleRollback(ServerMigrationService $service): int
    {
        if (! $this->option('app')) {
            $this->error('Rollback requires --app to specify which app to rollback.');

            return self::FAILURE;
        }

        $identifier = $this->option('app');
        $app = ServerMigrationApp::where('cloudways_app_id', $identifier)
            ->orWhere('app_label', $identifier)
            ->first();

        if (! $app) {
            $this->error("App not found: {$identifier}");

            return self::FAILURE;
        }

        if (empty($app->dns_records_updated)) {
            $this->warn("No DNS changes recorded for {$app->app_label}. Nothing to rollback.");

            return self::SUCCESS;
        }

        $this->info("Rolling back DNS for {$app->app_label}:");

        foreach ($app->dns_records_updated as $record) {
            $this->line("  A {$record['name']} → {$record['old_ip']}");
        }

        if (! $this->confirm('Proceed with rollback?')) {
            return self::SUCCESS;
        }

        $service->rollbackDns($app, fn (string $msg) => $this->line($msg));

        $this->info('Rollback complete.');

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

        $isDryRun = (bool) $this->option('dry-run');
        $sourceIp = config('services.cloudways.source_server_ip');
        $targetIp = config('services.cloudways.target_server_ip');

        $this->info(($isDryRun ? '[DRY RUN] ' : '')."DNS switch for {$app->app_label}:");
        $this->info("  Source IP: {$sourceIp}");
        $this->info("  Target IP: {$targetIp}");
        $this->newLine();

        $result = $service->switchDns($app, $isDryRun, fn (string $msg) => $this->line($msg));

        if (! $isDryRun && $result->status === 'dns_switched') {
            $this->info('DNS switch complete.');
        } elseif (! $isDryRun && $result->status === 'failed') {
            $this->error("DNS switch failed: {$result->last_error}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function handleBatch(ServerMigrationService $service): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $clonedApps = ServerMigrationApp::migratable()->byStatus('cloned')->get();

        if ($clonedApps->isEmpty()) {
            $this->info('No cloned apps ready for DNS switch.');

            return self::SUCCESS;
        }

        $sourceIp = config('services.cloudways.source_server_ip');
        $targetIp = config('services.cloudways.target_server_ip');

        $this->info(($isDryRun ? '[DRY RUN] ' : '')."DNS switch for {$clonedApps->count()} apps:");
        $this->info("  Source IP: {$sourceIp} → Target IP: {$targetIp}");
        $this->newLine();

        if (! $isDryRun && ! $this->confirm("Switch DNS for {$clonedApps->count()} apps?")) {
            return self::SUCCESS;
        }

        $stats = $service->switchDnsBatch(
            apps: $clonedApps,
            dryRun: $isDryRun,
            onProgress: fn (string $msg) => $this->line($msg)
        );

        $this->newLine();
        $this->info('DNS switch complete:');
        $this->info("  Switched: {$stats['switched']}");
        $this->info("  Failed: {$stats['failed']}");
        $this->info("  Skipped: {$stats['skipped']}");

        if ($stats['failed'] > 0) {
            $this->warn('Some apps failed. Run `php artisan migration:status --failed` to see details.');
        }

        return self::SUCCESS;
    }
}
