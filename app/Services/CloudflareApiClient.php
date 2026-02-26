<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareApiClient
{
    /**
     * Build an authenticated HTTP client.
     */
    protected function client(): PendingRequest
    {
        return Http::baseUrl(config('services.cloudflare.base_url'))
            ->withToken(config('services.cloudflare.api_token'))
            ->timeout(30)
            ->throw();
    }

    // ── Zones ─────────────────────────────────────────────────────────

    /**
     * Get zone details by domain name.
     * Extracts the root domain from subdomains (e.g., www.alpine.io → alpine.io).
     */
    public function getZoneByDomain(string $domain): ?array
    {
        try {
            // Extract root domain (last two parts) for zone lookup
            $parts = explode('.', $domain);
            $rootDomain = count($parts) > 2
                ? implode('.', array_slice($parts, -2))
                : $domain;

            $response = $this->client()->get('zones', [
                'name' => $rootDomain,
                'per_page' => 1,
            ]);

            $zones = $response->json('result') ?? [];

            return $zones[0] ?? null;
        } catch (\Exception $e) {
            Log::warning("Cloudflare: Failed to get zone for {$domain}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * List all zones on the account with pagination.
     *
     * @return array<int, array{id: string, name: string, status: string, ...}>
     */
    public function listZones(int $page = 1, int $perPage = 50): array
    {
        try {
            $response = $this->client()->get('zones', [
                'page' => $page,
                'per_page' => $perPage,
            ]);

            return $response->json('result') ?? [];
        } catch (\Exception $e) {
            Log::warning('Cloudflare: Failed to list zones: '.$e->getMessage());

            return [];
        }
    }

    // ── DNS Records ───────────────────────────────────────────────────

    /**
     * List DNS records for a zone, optionally filtered by type and name.
     *
     * @return array<int, array{id: string, type: string, name: string, content: string, proxied: bool, ...}>
     */
    public function listDnsRecords(string $zoneId, ?string $type = null, ?string $name = null): array
    {
        try {
            $params = ['per_page' => 100];

            if ($type) {
                $params['type'] = $type;
            }

            if ($name) {
                $params['name'] = $name;
            }

            $response = $this->client()->get("zones/{$zoneId}/dns_records", $params);

            return $response->json('result') ?? [];
        } catch (\Exception $e) {
            Log::warning("Cloudflare: Failed to list DNS records for zone {$zoneId}: ".$e->getMessage());

            return [];
        }
    }

    /**
     * Find all A records in a zone that point to a specific IP.
     *
     * @return array<int, array{id: string, name: string, content: string, proxied: bool, ...}>
     */
    public function findARecordsByIp(string $zoneId, string $ip): array
    {
        $records = $this->listDnsRecords($zoneId, 'A');

        return array_values(array_filter($records, fn (array $record) => $record['content'] === $ip));
    }

    /**
     * Update a DNS record (PATCH — partial update).
     */
    public function updateDnsRecord(string $zoneId, string $recordId, array $data): array
    {
        try {
            $response = $this->client()->patch("zones/{$zoneId}/dns_records/{$recordId}", $data);

            return $response->json('result') ?? [];
        } catch (\Exception $e) {
            Log::error("Cloudflare: Failed to update DNS record {$recordId} in zone {$zoneId}: ".$e->getMessage());

            throw $e;
        }
    }

    // ── Cache ─────────────────────────────────────────────────────────

    /**
     * Purge all cached content for a zone.
     */
    public function purgeCache(string $zoneId): bool
    {
        try {
            $response = $this->client()->post("zones/{$zoneId}/purge_cache", [
                'purge_everything' => true,
            ]);

            return $response->json('success') ?? false;
        } catch (\Exception $e) {
            Log::warning("Cloudflare: Failed to purge cache for zone {$zoneId}: ".$e->getMessage());

            return false;
        }
    }

    // ── SSL ───────────────────────────────────────────────────────────

    /**
     * Get current SSL/TLS settings for a zone.
     */
    public function getSslSettings(string $zoneId): array
    {
        try {
            $response = $this->client()->get("zones/{$zoneId}/settings/ssl");

            return $response->json('result') ?? [];
        } catch (\Exception $e) {
            Log::warning("Cloudflare: Failed to get SSL settings for zone {$zoneId}: ".$e->getMessage());

            return [];
        }
    }

    /**
     * Update SSL mode (off, flexible, full, strict).
     */
    public function updateSslMode(string $zoneId, string $mode): array
    {
        try {
            $response = $this->client()->patch("zones/{$zoneId}/settings/ssl", [
                'value' => $mode,
            ]);

            return $response->json('result') ?? [];
        } catch (\Exception $e) {
            Log::error("Cloudflare: Failed to update SSL mode for zone {$zoneId}: ".$e->getMessage());

            throw $e;
        }
    }

    // ── Connection Test ───────────────────────────────────────────────

    /**
     * Test the API connection by listing zones.
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->client()->get('zones', ['per_page' => 1]);

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
