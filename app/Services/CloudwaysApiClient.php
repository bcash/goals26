<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudwaysApiClient
{
    private const TOKEN_CACHE_KEY = 'cloudways:access_token';

    /**
     * Build an authenticated HTTP client, refreshing the token if needed.
     */
    protected function client(): PendingRequest
    {
        return Http::baseUrl(config('services.cloudways.base_url'))
            ->withToken($this->getAccessToken())
            ->timeout(60)
            ->throw();
    }

    /**
     * Get a valid access token, refreshing from cache or API as needed.
     */
    public function getAccessToken(): string
    {
        $cached = Cache::get(self::TOKEN_CACHE_KEY);

        if ($cached) {
            return $cached;
        }

        return $this->refreshAccessToken();
    }

    /**
     * Request a fresh access token from Cloudways OAuth endpoint.
     */
    public function refreshAccessToken(): string
    {
        $response = Http::asForm()
            ->post(config('services.cloudways.base_url').'/oauth/access_token', [
                'email' => config('services.cloudways.email'),
                'api_key' => config('services.cloudways.api_key'),
            ]);

        if ($response->failed()) {
            Log::error('Cloudways: Token refresh failed', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 500),
            ]);

            throw new \RuntimeException('Failed to obtain Cloudways access token.');
        }

        $token = $response->json('access_token');
        $ttl = (int) config('services.cloudways.token_ttl', 3300);
        Cache::put(self::TOKEN_CACHE_KEY, $token, $ttl);

        return $token;
    }

    // ── Server Operations ─────────────────────────────────────────────

    /**
     * List all servers on the account.
     *
     * @return array<int, array{id: int, label: string, public_ip: string, ...}>
     */
    public function listServers(): array
    {
        try {
            $response = $this->client()->get('server');

            return $response->json('servers') ?? [];
        } catch (\Exception $e) {
            Log::warning('Cloudways: Failed to list servers: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get all apps on a specific server.
     *
     * @return array<int, array{id: string, label: string, cname: string, ...}>
     */
    public function getServerApps(int $serverId): array
    {
        try {
            $servers = $this->listServers();

            foreach ($servers as $server) {
                if ((int) ($server['id'] ?? 0) === $serverId) {
                    return $server['apps'] ?? [];
                }
            }

            return [];
        } catch (\Exception $e) {
            Log::warning("Cloudways: Failed to get apps for server {$serverId}: ".$e->getMessage());

            return [];
        }
    }

    /**
     * Get details for a single app.
     */
    public function getApp(int $serverId, string $appId): array
    {
        try {
            $response = $this->client()->get("app/{$serverId}/{$appId}");

            return $response->json('app') ?? [];
        } catch (\Exception $e) {
            Log::warning("Cloudways: Failed to get app {$appId}: ".$e->getMessage());

            return [];
        }
    }

    // ── Clone Operations ──────────────────────────────────────────────

    /**
     * Initiate an app clone to a target server.
     * Returns the operation ID for polling.
     *
     * @throws \RuntimeException
     */
    public function cloneApp(int $sourceServerId, string $appId, string $appLabel, int $targetServerId): string
    {
        $response = $this->client()->post('app/clone', [
            'server_id' => $sourceServerId,
            'app_id' => $appId,
            'app_label' => $appLabel,
            'destination_server_id' => $targetServerId,
        ]);

        $operationId = $response->json('operation_id');

        if (! $operationId) {
            Log::error('Cloudways: Clone returned no operation_id', [
                'app_id' => $appId,
                'body' => substr($response->body(), 0, 500),
            ]);

            throw new \RuntimeException("Cloudways clone returned no operation_id for app {$appId}");
        }

        return (string) $operationId;
    }

    /**
     * Poll the status of an async operation.
     *
     * @return array{id: string, status: string, is_completed: bool, ...}
     */
    public function getOperationStatus(string $operationId): array
    {
        try {
            $response = $this->client()->get("operation/{$operationId}");

            return $response->json('operation') ?? $response->json() ?? [];
        } catch (\Exception $e) {
            Log::warning("Cloudways: Failed to get operation {$operationId}: ".$e->getMessage());

            return [];
        }
    }

    /**
     * Poll an operation until completion or timeout.
     *
     * @param  int  $maxWaitSeconds  Maximum time to wait (default 20 min)
     * @param  int  $pollIntervalSeconds  Seconds between polls (default 30)
     *
     * @throws \RuntimeException
     */
    public function waitForOperation(
        string $operationId,
        int $maxWaitSeconds = 1200,
        int $pollIntervalSeconds = 30,
        ?\Closure $onProgress = null
    ): array {
        $startTime = time();

        while (true) {
            $operation = $this->getOperationStatus($operationId);
            $status = $operation['status'] ?? 'unknown';
            $isCompleted = (bool) ($operation['is_completed'] ?? ($status === 'Operation completed' || $status === '1'));

            if ($onProgress) {
                $onProgress($status, time() - $startTime);
            }

            if ($isCompleted) {
                return $operation;
            }

            if ((time() - $startTime) >= $maxWaitSeconds) {
                throw new \RuntimeException("Cloudways operation {$operationId} timed out after {$maxWaitSeconds} seconds.");
            }

            sleep($pollIntervalSeconds);
        }
    }

    // ── SSL Operations ────────────────────────────────────────────────

    /**
     * Install Let's Encrypt SSL on an app.
     *
     * @throws \RuntimeException
     */
    public function installSsl(int $serverId, string $appId, string $domain, array $additionalDomains = []): array
    {
        $payload = [
            'server_id' => $serverId,
            'app_id' => $appId,
            'ssl_email' => config('services.cloudways.email'),
            'wild_card' => false,
            'ssl_domain' => $domain,
        ];

        if ($additionalDomains) {
            $payload['ssl_domains'] = implode(',', $additionalDomains);
        }

        $response = $this->client()->post('security/lets_encrypt_install', $payload);

        return $response->json() ?? [];
    }

    // ── Connection Test ───────────────────────────────────────────────

    /**
     * Test the API connection by listing servers.
     */
    public function testConnection(): bool
    {
        try {
            $this->getAccessToken();
            $servers = $this->listServers();

            return count($servers) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }
}
