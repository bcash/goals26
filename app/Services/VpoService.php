<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VpoService
{
    private int $cacheTtl;

    public function __construct(private VpoApiClient $client)
    {
        $this->cacheTtl = config('vpo.cache_ttl', 300);
    }

    /**
     * Check if VPO integration is available (configured and enabled).
     */
    public function isAvailable(): bool
    {
        return config('vpo.enabled', true) && ! empty(config('vpo.token'));
    }

    /**
     * Search for accounts by query string.
     *
     * @return array<int, array{id: int, name: string, slug: ?string, industry: array}>
     */
    public function searchAccounts(string $query, int $limit = 25): array
    {
        return $this->cached('accounts', [
            'search' => $query,
            'per_page' => $limit,
        ]);
    }

    /**
     * Get full account details by ID (includes websites, domains, children).
     *
     * @return array{id: int, name: string, slug: ?string, contact_name: ?string, ...}|null
     */
    public function getAccount(string $accountId): ?array
    {
        $result = $this->cached("accounts/{$accountId}", []);

        return ! empty($result) ? $result : null;
    }

    /**
     * List projects (VPO tasks with is_project=true) for an account.
     *
     * @return array<int, array{id: int, name: string, status: string, type: string}>
     */
    public function getProjects(string $accountId): array
    {
        return $this->cached('tasks', [
            'account_id' => $accountId,
            'is_project' => true,
            'per_page' => 100,
        ]);
    }

    /**
     * List tasks (non-project) for an account.
     *
     * @return array<int, array{id: int, name: string, status: string, type: string}>
     */
    public function getTasks(string $accountId): array
    {
        return $this->cached('tasks', [
            'account_id' => $accountId,
            'is_project' => false,
            'per_page' => 100,
        ]);
    }

    /**
     * List invoices for an account.
     *
     * @return array<int, array{id: int, source: string, number: string, status: string, total_cents: int}>
     */
    public function getInvoices(string $accountId): array
    {
        return $this->cached('invoices', [
            'account_id' => $accountId,
            'per_page' => 100,
        ]);
    }

    /**
     * List all servers.
     *
     * @return array<int, array{id: int, name: string, status: ?string, public_ip_address: ?string}>
     */
    public function getServers(int $perPage = 100): array
    {
        return $this->cached('servers', ['per_page' => $perPage]);
    }

    /**
     * List domains, optionally filtered by account.
     *
     * @return array<int, array{id: int, name: string, account_id: ?int, registration_expires: string}>
     */
    public function getDomains(?string $accountId = null, int $perPage = 100): array
    {
        $query = ['per_page' => $perPage];

        if ($accountId) {
            $query['account_id'] = $accountId;
        }

        return $this->cached('domains', $query);
    }

    /**
     * List websites, optionally filtered by account or server.
     *
     * @return array<int, array{id: int, name: string, public_url: ?string, server_id: ?int}>
     */
    public function getWebsites(?string $accountId = null, int $perPage = 100): array
    {
        $query = ['per_page' => $perPage];

        if ($accountId) {
            $query['account_id'] = $accountId;
        }

        return $this->cached('websites', $query);
    }

    /**
     * Call a VPO API endpoint with caching and graceful error handling.
     *
     * Never throws — returns empty array on failure.
     */
    private function cached(string $endpoint, array $query): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        $cacheKey = 'vpo:'.$endpoint.':'.md5(json_encode($query));

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($endpoint, $query) {
            try {
                return $this->client->get($endpoint, $query);
            } catch (\Throwable $e) {
                Log::warning("VPO API call failed: {$endpoint}", [
                    'error' => $e->getMessage(),
                    'query' => $query,
                ]);

                return [];
            }
        });
    }
}
