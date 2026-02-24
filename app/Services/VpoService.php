<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class VpoService
{
    private int $cacheTtl;

    public function __construct(private VpoMcpClient $client)
    {
        $this->cacheTtl = config('vpo.cache_ttl', 300);
    }

    /**
     * Check if VPO integration is available (configured and enabled).
     */
    public function isAvailable(): bool
    {
        return config('vpo.enabled', true) && ! empty(config('vpo.key'));
    }

    /**
     * Search for accounts by query string.
     *
     * @return array<int, array{id: string, name: string, status: string}>
     */
    public function searchAccounts(string $query, int $limit = 25): array
    {
        return $this->cached('search-accounts', compact('query', 'limit'));
    }

    /**
     * Get full account details by ID.
     *
     * @return array{id: string, name: string, status: string, ...}|null
     */
    public function getAccount(string $accountId): ?array
    {
        $result = $this->cached('get-account-details', ['account_id' => $accountId]);

        return ! empty($result) ? $result : null;
    }

    /**
     * List contacts for an account.
     *
     * @return array<int, array{id: string, name: string, email: string, role: string}>
     */
    public function getContacts(string $accountId): array
    {
        return $this->cached('list-account-contacts', ['account_id' => $accountId]);
    }

    /**
     * List projects for an account.
     *
     * @return array<int, array{id: string, name: string, status: string}>
     */
    public function getProjects(string $accountId): array
    {
        return $this->cached('list-account-projects', ['account_id' => $accountId]);
    }

    /**
     * List invoices for an account.
     *
     * @return array<int, array{id: string, number: string, amount: float, status: string}>
     */
    public function getInvoices(string $accountId): array
    {
        return $this->cached('list-account-invoices', ['account_id' => $accountId]);
    }

    /**
     * List tickets for an account.
     *
     * @return array<int, array{id: string, subject: string, status: string}>
     */
    public function getTickets(string $accountId): array
    {
        return $this->cached('list-account-tickets', ['account_id' => $accountId]);
    }

    /**
     * Call a VPO tool with caching and graceful error handling.
     *
     * Never throws — returns empty array on failure.
     */
    private function cached(string $tool, array $arguments): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        $cacheKey = 'vpo:' . $tool . ':' . md5(json_encode($arguments));

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($tool, $arguments) {
            try {
                return $this->client->callTool($tool, $arguments);
            } catch (\Throwable $e) {
                Log::warning("VPO service call failed: {$tool}", [
                    'error' => $e->getMessage(),
                    'arguments' => $arguments,
                ]);

                return [];
            }
        });
    }
}
