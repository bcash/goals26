<?php

namespace App\Services;

use App\Models\ServerMigrationApp;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ServerMigrationService
{
    public function __construct(
        protected CloudwaysApiClient $cloudways,
        protected CloudflareApiClient $cloudflare
    ) {}

    // ── Inventory ─────────────────────────────────────────────────────

    /**
     * Fetch all apps from the source server and sync to tracking table.
     * Uses updateOrCreate for idempotency (safe to re-run).
     *
     * @return Collection<int, ServerMigrationApp>
     */
    public function inventory(?\Closure $onProgress = null): Collection
    {
        $serverId = (int) config('services.cloudways.source_server_id');
        $apps = $this->cloudways->getServerApps($serverId);

        if (empty($apps)) {
            Log::warning("Migration: No apps found on server {$serverId}");

            return collect();
        }

        $records = collect();

        foreach ($apps as $index => $app) {
            $appId = (string) ($app['id'] ?? '');
            $label = $app['label'] ?? 'unknown';
            $cname = $app['cname'] ?? null;

            // Extract domains from app data
            $domains = [];
            if (! empty($app['app_fqdn'])) {
                $domains[] = $app['app_fqdn'];
            }
            if (! empty($app['aliases'])) {
                $domains = array_merge($domains, (array) $app['aliases']);
            }

            $record = ServerMigrationApp::updateOrCreate(
                ['cloudways_app_id' => $appId],
                [
                    'app_label' => $label,
                    'app_cname' => $cname,
                    'domains' => $domains ?: null,
                    'primary_domain' => $domains[0] ?? null,
                ]
            );

            $records->push($record);

            if ($onProgress) {
                $onProgress($index + 1, count($apps), $label);
            }
        }

        return $records;
    }

    // ── Clone Workflow ─────────────────────────────────────────────────

    /**
     * Clone a single app to the target server.
     * Blocks until the clone operation completes (polling).
     */
    public function cloneApp(ServerMigrationApp $app, ?\Closure $onProgress = null): ServerMigrationApp
    {
        $sourceServerId = (int) config('services.cloudways.source_server_id');
        $targetServerId = (int) config('services.cloudways.target_server_id');

        $app->markStatus('cloning');
        $app->update(['clone_started_at' => now()]);

        try {
            $operationId = $this->cloudways->cloneApp(
                $sourceServerId,
                $app->cloudways_app_id,
                $app->app_label,
                $targetServerId
            );

            $app->update(['clone_operation_id' => $operationId]);

            if ($onProgress) {
                $onProgress("Clone started for {$app->app_label} (operation: {$operationId})");
            }

            // Poll until complete
            $this->cloudways->waitForOperation(
                $operationId,
                maxWaitSeconds: 1200,
                pollIntervalSeconds: 30,
                onProgress: function (string $status, int $elapsed) use ($app, $onProgress) {
                    if ($onProgress) {
                        $minutes = intdiv($elapsed, 60);
                        $onProgress("  {$app->app_label}: {$status} ({$minutes}m elapsed)");
                    }
                }
            );

            $app->update([
                'status' => 'cloned',
                'clone_completed_at' => now(),
                'last_error' => null,
            ]);

            // Try to find the new app ID on the target server
            $this->resolveTargetAppId($app, $targetServerId);

            if ($onProgress) {
                $onProgress("Clone complete: {$app->app_label}");
            }
        } catch (\Exception $e) {
            $app->markFailed("Clone failed: {$e->getMessage()}");
            Log::error("Migration: Clone failed for {$app->app_label}", ['error' => $e->getMessage()]);
        }

        return $app->refresh();
    }

    /**
     * Clone multiple apps in batches with configurable concurrency.
     * Skips already-cloned apps for resumability.
     *
     * @return array{cloned: int, failed: int, skipped: int}
     */
    public function cloneBatch(
        ?Collection $apps = null,
        int $batchSize = 5,
        bool $dryRun = false,
        ?\Closure $onProgress = null
    ): array {
        $apps = $apps ?? ServerMigrationApp::migratable()->byStatus('pending')->get();
        $stats = ['cloned' => 0, 'failed' => 0, 'skipped' => 0];

        if ($apps->isEmpty()) {
            return $stats;
        }

        $batches = $apps->chunk($batchSize);
        $batchNumber = 0;

        foreach ($batches as $batch) {
            $batchNumber++;

            if ($onProgress) {
                $onProgress("Batch {$batchNumber}/".count($batches).' ('.count($batch).' apps)');
            }

            if ($dryRun) {
                $stats['skipped'] += count($batch);

                continue;
            }

            // Proactively refresh token before each batch
            $this->cloudways->refreshAccessToken();

            // Start all clones in the batch
            $activeOperations = [];

            foreach ($batch as $app) {
                if (! $app->canClone()) {
                    $stats['skipped']++;

                    continue;
                }

                try {
                    $sourceServerId = (int) config('services.cloudways.source_server_id');
                    $targetServerId = (int) config('services.cloudways.target_server_id');

                    $app->markStatus('cloning');
                    $app->update(['clone_started_at' => now()]);

                    $operationId = $this->cloudways->cloneApp(
                        $sourceServerId,
                        $app->cloudways_app_id,
                        $app->app_label,
                        $targetServerId
                    );

                    $app->update(['clone_operation_id' => $operationId]);
                    $activeOperations[$operationId] = $app;

                    if ($onProgress) {
                        $onProgress("  Started: {$app->app_label} (op: {$operationId})");
                    }

                    // Small delay between clone API calls to respect rate limits
                    usleep(200_000);
                } catch (\Exception $e) {
                    $app->markFailed("Clone start failed: {$e->getMessage()}");
                    $stats['failed']++;
                    Log::error("Migration: Clone start failed for {$app->app_label}", ['error' => $e->getMessage()]);
                }
            }

            // Poll all active operations until all complete
            $this->pollBatchOperations($activeOperations, $stats, $onProgress);
        }

        return $stats;
    }

    /**
     * Resume polling for apps stuck in 'cloning' status.
     */
    public function resumeCloning(?\Closure $onProgress = null): int
    {
        $stuckApps = ServerMigrationApp::migratable()
            ->byStatus('cloning')
            ->whereNotNull('clone_operation_id')
            ->get();

        if ($stuckApps->isEmpty()) {
            return 0;
        }

        $stats = ['cloned' => 0, 'failed' => 0, 'skipped' => 0];
        $activeOperations = [];

        foreach ($stuckApps as $app) {
            $activeOperations[$app->clone_operation_id] = $app;
        }

        if ($onProgress) {
            $onProgress("Resuming {$stuckApps->count()} stuck clone operations...");
        }

        $this->pollBatchOperations($activeOperations, $stats, $onProgress);

        return $stats['cloned'];
    }

    // ── DNS Workflow ──────────────────────────────────────────────────

    /**
     * Switch DNS for a single app.
     * Finds all A records pointing to source IP and updates to target IP.
     */
    public function switchDns(ServerMigrationApp $app, bool $dryRun = false, ?\Closure $onProgress = null): ServerMigrationApp
    {
        $sourceIp = config('services.cloudways.source_server_ip');
        $targetIp = config('services.cloudways.target_server_ip');

        if (! $app->primary_domain) {
            $app->markFailed('No primary domain set');

            return $app->refresh();
        }

        $app->markStatus('dns_switching');

        try {
            $zone = $this->cloudflare->getZoneByDomain($app->primary_domain);

            if (! $zone) {
                $app->markFailed("Cloudflare zone not found for {$app->primary_domain}");

                return $app->refresh();
            }

            $zoneId = $zone['id'];
            $records = $this->cloudflare->findARecordsByIp($zoneId, $sourceIp);

            if (empty($records)) {
                if ($onProgress) {
                    $onProgress("  {$app->primary_domain}: No A records pointing to source IP");
                }

                $app->update([
                    'status' => 'dns_switched',
                    'dns_switched_at' => now(),
                    'dns_records_updated' => [],
                ]);

                return $app->refresh();
            }

            $updatedRecords = [];

            foreach ($records as $record) {
                if ($onProgress) {
                    $prefix = $dryRun ? '[DRY RUN] ' : '';
                    $onProgress("  {$prefix}A {$record['name']} {$sourceIp} → {$targetIp}");
                }

                if (! $dryRun) {
                    $this->cloudflare->updateDnsRecord($zoneId, $record['id'], [
                        'content' => $targetIp,
                    ]);
                }

                $updatedRecords[] = [
                    'zone_id' => $zoneId,
                    'record_id' => $record['id'],
                    'name' => $record['name'],
                    'old_ip' => $sourceIp,
                    'new_ip' => $targetIp,
                    'proxied' => $record['proxied'] ?? false,
                ];

                usleep(100_000); // Rate-limit courtesy
            }

            if (! $dryRun) {
                // Purge Cloudflare cache after DNS switch
                $this->cloudflare->purgeCache($zoneId);

                $app->update([
                    'status' => 'dns_switched',
                    'dns_switched_at' => now(),
                    'dns_records_updated' => $updatedRecords,
                    'last_error' => null,
                ]);

                if ($onProgress) {
                    $onProgress("  DNS switched + cache purged for {$app->primary_domain} (".count($updatedRecords).' records)');
                }
            }
        } catch (\Exception $e) {
            $app->markFailed("DNS switch failed: {$e->getMessage()}");
            Log::error("Migration: DNS switch failed for {$app->app_label}", ['error' => $e->getMessage()]);
        }

        return $app->refresh();
    }

    /**
     * Switch DNS for all cloned apps.
     *
     * @return array{switched: int, failed: int, skipped: int}
     */
    public function switchDnsBatch(
        ?Collection $apps = null,
        bool $dryRun = false,
        ?\Closure $onProgress = null
    ): array {
        $apps = $apps ?? ServerMigrationApp::migratable()->byStatus('cloned')->get();
        $stats = ['switched' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($apps as $app) {
            if (! $app->canSwitchDns() && ! $dryRun) {
                $stats['skipped']++;

                continue;
            }

            $result = $this->switchDns($app, $dryRun, $onProgress);

            if ($result->status === 'dns_switched') {
                $stats['switched']++;
            } elseif ($result->status === 'failed') {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    // ── Verification ─────────────────────────────────────────────────

    /**
     * HTTP-verify a single migrated app.
     */
    public function verify(ServerMigrationApp $app, int $timeout = 15, ?\Closure $onProgress = null): ServerMigrationApp
    {
        if (! $app->primary_domain) {
            $app->markFailed('No primary domain to verify');

            return $app->refresh();
        }

        $app->markStatus('verifying');

        try {
            $url = "https://{$app->primary_domain}";
            $start = microtime(true);

            $response = Http::timeout($timeout)
                ->withoutVerifying()
                ->get($url);

            $elapsed = round((microtime(true) - $start) * 1000);
            $statusCode = $response->status();
            $isOk = $response->successful();
            $hasHtml = str_contains($response->body(), '</html>') || str_contains($response->body(), '</HTML>');

            $notes = [];
            $notes[] = "HTTP {$statusCode} in {$elapsed}ms";

            if (! $hasHtml) {
                $notes[] = 'WARNING: No closing </html> tag found';
            }

            $passed = $isOk && $hasHtml;

            $app->update([
                'status' => $passed ? 'verified' : 'failed',
                'verified' => $passed,
                'http_status_code' => $statusCode,
                'verification_notes' => implode('; ', $notes),
                'verified_at' => now(),
                'last_error' => $passed ? null : implode('; ', $notes),
            ]);

            if ($onProgress) {
                $icon = $passed ? "\u{2705}" : "\u{274c}";
                $onProgress("  {$icon} {$app->primary_domain}: {$statusCode} ({$elapsed}ms)");
            }
        } catch (\Exception $e) {
            $app->update([
                'status' => 'failed',
                'verified' => false,
                'verification_notes' => "Request failed: {$e->getMessage()}",
                'verified_at' => now(),
                'last_error' => "Verify failed: {$e->getMessage()}",
            ]);

            if ($onProgress) {
                $onProgress("  \u{274c} {$app->primary_domain}: {$e->getMessage()}");
            }
        }

        return $app->refresh();
    }

    /**
     * Verify all apps that have completed DNS switch.
     *
     * @return array{verified: int, failed: int}
     */
    public function verifyBatch(?Collection $apps = null, int $timeout = 15, ?\Closure $onProgress = null): array
    {
        $apps = $apps ?? ServerMigrationApp::migratable()
            ->whereIn('status', ['dns_switched', 'verified', 'failed'])
            ->get();

        $stats = ['verified' => 0, 'failed' => 0];

        foreach ($apps as $app) {
            $result = $this->verify($app, $timeout, $onProgress);

            if ($result->verified) {
                $stats['verified']++;
            } else {
                $stats['failed']++;
            }
        }

        return $stats;
    }

    // ── Rollback ─────────────────────────────────────────────────────

    /**
     * Rollback DNS for an app (restore original IP).
     */
    public function rollbackDns(ServerMigrationApp $app, ?\Closure $onProgress = null): ServerMigrationApp
    {
        $updatedRecords = $app->dns_records_updated ?? [];

        if (empty($updatedRecords)) {
            if ($onProgress) {
                $onProgress("  {$app->app_label}: No DNS changes to rollback");
            }

            return $app;
        }

        try {
            foreach ($updatedRecords as $record) {
                $this->cloudflare->updateDnsRecord($record['zone_id'], $record['record_id'], [
                    'content' => $record['old_ip'],
                ]);

                if ($onProgress) {
                    $onProgress("  Restored A {$record['name']} → {$record['old_ip']}");
                }

                usleep(100_000);
            }

            // Purge cache after rollback
            $zoneId = $updatedRecords[0]['zone_id'] ?? null;
            if ($zoneId) {
                $this->cloudflare->purgeCache($zoneId);
            }

            $app->update([
                'status' => 'cloned', // Back to pre-DNS state
                'dns_switched_at' => null,
                'dns_records_updated' => null,
                'verified' => false,
                'verified_at' => null,
                'last_error' => null,
            ]);

            if ($onProgress) {
                $onProgress("  Rollback complete for {$app->app_label}");
            }
        } catch (\Exception $e) {
            $app->markFailed("DNS rollback failed: {$e->getMessage()}");
            Log::error("Migration: DNS rollback failed for {$app->app_label}", ['error' => $e->getMessage()]);
        }

        return $app->refresh();
    }

    // ── Status & Reporting ────────────────────────────────────────────

    /**
     * Get migration summary statistics.
     *
     * @return array{total: int, migratable: int, pending: int, cloning: int, cloned: int, dns_switching: int, dns_switched: int, verifying: int, verified: int, completed: int, failed: int}
     */
    public function getSummary(): array
    {
        $all = ServerMigrationApp::query();

        return [
            'total' => (clone $all)->count(),
            'migratable' => (clone $all)->migratable()->count(),
            'pending' => (clone $all)->migratable()->byStatus('pending')->count(),
            'cloning' => (clone $all)->migratable()->byStatus('cloning')->count(),
            'cloned' => (clone $all)->migratable()->byStatus('cloned')->count(),
            'dns_switching' => (clone $all)->migratable()->byStatus('dns_switching')->count(),
            'dns_switched' => (clone $all)->migratable()->byStatus('dns_switched')->count(),
            'verifying' => (clone $all)->migratable()->byStatus('verifying')->count(),
            'verified' => (clone $all)->migratable()->byStatus('verified')->count(),
            'completed' => (clone $all)->migratable()->byStatus('completed')->count(),
            'failed' => (clone $all)->migratable()->failed()->count(),
        ];
    }

    // ── Private Helpers ──────────────────────────────────────────────

    /**
     * Poll a batch of active operations until all complete.
     *
     * @param  array<string, ServerMigrationApp>  $activeOperations
     * @param  array{cloned: int, failed: int, skipped: int}  $stats
     */
    private function pollBatchOperations(array &$activeOperations, array &$stats, ?\Closure $onProgress = null): void
    {
        $maxWait = 1200; // 20 minutes
        $startTime = time();

        while (! empty($activeOperations) && (time() - $startTime) < $maxWait) {
            foreach ($activeOperations as $opId => $app) {
                $operation = $this->cloudways->getOperationStatus($opId);
                $status = $operation['status'] ?? 'unknown';
                $isCompleted = (bool) ($operation['is_completed'] ?? ($status === 'Operation completed' || $status === '1'));

                if ($isCompleted) {
                    $targetServerId = (int) config('services.cloudways.target_server_id');

                    $app->update([
                        'status' => 'cloned',
                        'clone_completed_at' => now(),
                        'last_error' => null,
                    ]);

                    $this->resolveTargetAppId($app, $targetServerId);
                    $stats['cloned']++;

                    if ($onProgress) {
                        $onProgress("  Cloned: {$app->app_label}");
                    }

                    unset($activeOperations[$opId]);
                }
            }

            if (! empty($activeOperations)) {
                if ($onProgress) {
                    $elapsed = intdiv(time() - $startTime, 60);
                    $onProgress('  Waiting... '.count($activeOperations)." operations remaining ({$elapsed}m)");
                }

                sleep(30);
            }
        }

        // Anything still running after max wait is failed
        foreach ($activeOperations as $opId => $app) {
            $app->markFailed("Clone operation timed out after {$maxWait}s");
            $stats['failed']++;
        }
    }

    /**
     * After cloning, find the new app ID on the target server by matching the label.
     */
    private function resolveTargetAppId(ServerMigrationApp $app, int $targetServerId): void
    {
        try {
            $targetApps = $this->cloudways->getServerApps($targetServerId);

            foreach ($targetApps as $targetApp) {
                if (($targetApp['label'] ?? '') === $app->app_label) {
                    $app->update(['target_app_id' => (string) $targetApp['id']]);

                    return;
                }
            }
        } catch (\Exception $e) {
            Log::warning("Migration: Could not resolve target app ID for {$app->app_label}: ".$e->getMessage());
        }
    }
}
