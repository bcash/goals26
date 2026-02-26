<?php

namespace App\Console\Commands;

use App\Models\ServerMigrationApp;
use App\Services\ServerMigrationService;
use Illuminate\Console\Command;

class MigrationStatus extends Command
{
    protected $signature = 'migration:status
                            {--failed : Only show failed apps}
                            {--category= : Filter by category (client, government, staging, internal)}
                            {--status= : Filter by status (pending, cloning, cloned, dns_switched, verified, completed, failed)}';

    protected $description = 'Display current migration status for all tracked apps';

    public function handle(ServerMigrationService $service): int
    {
        $summary = $service->getSummary();

        if ($summary['total'] === 0) {
            $this->warn('No apps in tracking table. Run `php artisan migration:inventory` first.');

            return self::SUCCESS;
        }

        // Summary bar
        $this->displayProgressBar($summary);
        $this->newLine();

        // Summary table
        $this->table(
            ['Status', 'Count'],
            [
                ['Pending', $summary['pending']],
                ['Cloning', $summary['cloning']],
                ['Cloned', $summary['cloned']],
                ['DNS switching', $summary['dns_switching']],
                ['DNS switched', $summary['dns_switched']],
                ['Verifying', $summary['verifying']],
                ['Verified', $summary['verified']],
                ['Completed', $summary['completed']],
                ['Failed', $summary['failed']],
            ]
        );

        // Detailed table
        $this->displayAppsTable();

        return self::SUCCESS;
    }

    private function displayProgressBar(array $summary): void
    {
        $total = $summary['migratable'];
        $done = $summary['completed'] + $summary['verified'];
        $failed = $summary['failed'];
        $inProgress = $summary['cloning'] + $summary['dns_switching'] + $summary['verifying'];

        if ($total === 0) {
            return;
        }

        $barLength = 40;
        $doneChars = (int) round(($done / $total) * $barLength);
        $failedChars = (int) round(($failed / $total) * $barLength);
        $progressChars = (int) round(($inProgress / $total) * $barLength);
        $pendingChars = $barLength - $doneChars - $failedChars - $progressChars;

        $bar = str_repeat('█', $doneChars)
            .str_repeat('▓', $progressChars)
            .str_repeat('░', max(0, $pendingChars))
            .str_repeat('✗', $failedChars);

        $this->line("  [{$bar}] {$done}/{$total} complete".($failed > 0 ? " ({$failed} failed)" : ''));
    }

    private function displayAppsTable(): void
    {
        $query = ServerMigrationApp::migratable()->orderBy('status')->orderBy('app_label');

        if ($this->option('failed')) {
            $query = ServerMigrationApp::migratable()->failed()->orderBy('app_label');
        }

        if ($this->option('category')) {
            $query->byCategory($this->option('category'));
        }

        if ($this->option('status')) {
            $query = ServerMigrationApp::migratable()
                ->byStatus($this->option('status'))
                ->orderBy('app_label');
        }

        $apps = $query->get();

        if ($apps->isEmpty()) {
            $this->comment('No apps match the filter criteria.');

            return;
        }

        $rows = $apps->map(fn (ServerMigrationApp $app) => [
            $app->app_label,
            $app->primary_domain ?? '-',
            $app->category,
            $this->formatStatus($app->status),
            $app->last_error ? substr($app->last_error, 0, 60) : '-',
            $app->updated_at?->diffForHumans() ?? '-',
        ]);

        $this->table(
            ['Label', 'Domain', 'Category', 'Status', 'Last Error', 'Updated'],
            $rows->toArray()
        );
    }

    private function formatStatus(string $status): string
    {
        return match ($status) {
            'pending' => '⏳ pending',
            'cloning' => '🔄 cloning',
            'cloned' => '📦 cloned',
            'dns_switching' => '🔄 dns_switching',
            'dns_switched' => '🌐 dns_switched',
            'verifying' => '🔍 verifying',
            'verified' => '✅ verified',
            'completed' => '🎉 completed',
            'failed' => '❌ failed',
            default => $status,
        };
    }
}
