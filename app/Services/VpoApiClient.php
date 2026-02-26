<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VpoApiClient
{
    private string $baseUrl;

    private string $token;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('vpo.base_url', ''), '/');
        $this->token = config('vpo.token', '');
        $this->timeout = config('vpo.timeout', 30);
    }

    /**
     * Send a GET request to the VPO REST API.
     *
     * @return array The response data (unwrapped from the `data` envelope).
     */
    public function get(string $endpoint, array $query = []): array
    {
        $url = $this->baseUrl.'/'.ltrim($endpoint, '/');

        $response = $this->send($url, $query);

        if ($response->failed()) {
            Log::error("VPO API request failed: GET {$endpoint}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("VPO API request failed: GET {$endpoint} (HTTP {$response->status()})");
        }

        $json = $response->json();

        return $json['data'] ?? [];
    }

    /**
     * Send a GET request and return the full paginated response (data + meta).
     *
     * @return array{data: array, meta: array, links: array}
     */
    public function getPaginated(string $endpoint, array $query = []): array
    {
        $url = $this->baseUrl.'/'.ltrim($endpoint, '/');

        $response = $this->send($url, $query);

        if ($response->failed()) {
            Log::error("VPO API request failed: GET {$endpoint}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \RuntimeException("VPO API request failed: GET {$endpoint} (HTTP {$response->status()})");
        }

        return $response->json();
    }

    private function send(string $url, array $query): Response
    {
        return Http::withToken($this->token)
            ->accept('application/json')
            ->timeout($this->timeout)
            ->get($url, $query);
    }
}
