<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VpoMcpClient
{
    private string $url;

    private string $key;

    private int $timeout;

    private int $sessionTtl;

    private int $requestId = 0;

    private const CACHE_KEY_SESSION = 'vpo:mcp:session_id';

    public function __construct()
    {
        $this->url = config('vpo.url');
        $this->key = config('vpo.key', '');
        $this->timeout = config('vpo.timeout', 30);
        $this->sessionTtl = config('vpo.session_ttl', 1800);
    }

    /**
     * Call an MCP tool on the VPO server.
     *
     * Handles session initialization and retry on session expiry.
     */
    public function callTool(string $tool, array $arguments = []): array
    {
        $sessionId = $this->getOrCreateSession();

        $response = $this->sendJsonRpc('tools/call', [
            'name' => $tool,
            'arguments' => $arguments,
        ], $sessionId);

        // Retry once if session expired (404 or specific error)
        if ($response->status() === 404 || $this->isSessionExpired($response)) {
            Cache::forget(self::CACHE_KEY_SESSION);
            $sessionId = $this->getOrCreateSession();

            $response = $this->sendJsonRpc('tools/call', [
                'name' => $tool,
                'arguments' => $arguments,
            ], $sessionId);
        }

        if ($response->failed()) {
            Log::error("VPO MCP call failed: {$tool}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException("VPO MCP call failed: {$tool} (HTTP {$response->status()})");
        }

        return $this->parseResponse($response->json());
    }

    /**
     * Get an existing session ID from cache or initialize a new one.
     */
    private function getOrCreateSession(): string
    {
        return Cache::remember(self::CACHE_KEY_SESSION, $this->sessionTtl, function () {
            return $this->initialize();
        });
    }

    /**
     * Perform the MCP initialize handshake.
     *
     * Sends `initialize` request, then `notifications/initialized` notification.
     * Returns the session ID from the response header.
     */
    private function initialize(): string
    {
        $response = $this->sendJsonRpc('initialize', [
            'protocolVersion' => '2025-03-26',
            'capabilities' => new \stdClass,
            'clientInfo' => [
                'name' => 'solas-run',
                'version' => '1.0.0',
            ],
        ]);

        if ($response->failed()) {
            throw new \RuntimeException("VPO MCP initialize failed (HTTP {$response->status()})");
        }

        $sessionId = $response->header('MCP-Session-Id') ?? $response->header('mcp-session-id');

        if (! $sessionId) {
            throw new \RuntimeException('VPO MCP initialize did not return a session ID');
        }

        // Send initialized notification (no response expected)
        $this->sendNotification('notifications/initialized', [], $sessionId);

        return $sessionId;
    }

    /**
     * Send a JSON-RPC 2.0 request to the VPO MCP server.
     */
    private function sendJsonRpc(string $method, array $params, ?string $sessionId = null): \Illuminate\Http\Client\Response
    {
        $this->requestId++;

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$this->key}",
        ];

        if ($sessionId) {
            $headers['MCP-Session-Id'] = $sessionId;
        }

        return Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->post($this->url, [
                'jsonrpc' => '2.0',
                'id' => $this->requestId,
                'method' => $method,
                'params' => $params,
            ]);
    }

    /**
     * Send a JSON-RPC 2.0 notification (no id, no response expected).
     */
    private function sendNotification(string $method, array $params, string $sessionId): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Authorization' => "Bearer {$this->key}",
            'MCP-Session-Id' => $sessionId,
        ];

        Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->post($this->url, [
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => $params,
            ]);
    }

    /**
     * Parse the JSON-RPC response, extracting tool result content.
     *
     * MCP tool responses follow: result.content[0].text → JSON string
     */
    private function parseResponse(array $json): array
    {
        // Check for JSON-RPC error
        if (isset($json['error'])) {
            throw new \RuntimeException(
                "VPO MCP error: {$json['error']['message']} (code: {$json['error']['code']})"
            );
        }

        $contentText = data_get($json, 'result.content.0.text');

        if ($contentText) {
            $decoded = json_decode($contentText, true);

            return $decoded ?? ['text' => $contentText];
        }

        return $json['result'] ?? [];
    }

    /**
     * Check if the response indicates an expired session.
     */
    private function isSessionExpired(\Illuminate\Http\Client\Response $response): bool
    {
        if ($response->status() === 401 || $response->status() === 403) {
            return true;
        }

        $body = $response->json();

        return isset($body['error']['code']) && $body['error']['code'] === -32600;
    }
}
