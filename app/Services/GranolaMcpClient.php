<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GranolaMcpClient
{
    private string $url;

    private int $timeout;

    private int $requestId = 0;

    private const SESSION_TTL = 1800; // 30 minutes

    public function __construct(
        protected GranolaOAuthService $oauth
    ) {
        $this->url = config('services.granola.mcp_url', 'https://mcp.granola.ai/mcp');
        $this->timeout = 30;
    }

    /**
     * List recent meetings from Granola.
     *
     * Parses Granola's XML-like response into structured meeting arrays.
     *
     * @return array<int, array{id: string, title: string, date: string, attendees: string[]}> List of meeting summaries
     */
    public function listMeetings(User $user, int $limit = 25): array
    {
        $result = $this->callTool($user, 'list_meetings', [
            'limit' => $limit,
        ]);

        // Granola returns meetings as XML-like text in the 'text' key
        $text = $result['text'] ?? '';

        return $this->parseMeetingsText($text);
    }

    /**
     * Search meetings by query.
     *
     * @return array Matching meetings
     */
    public function queryMeetings(User $user, string $query, int $limit = 25): array
    {
        return $this->callTool($user, 'query_granola_meetings', [
            'query' => $query,
            'limit' => $limit,
        ]);
    }

    /**
     * Get meeting details (structured notes).
     *
     * @return array|null Meeting details, or null on failure
     */
    public function getMeeting(User $user, string $meetingId): ?array
    {
        try {
            return $this->callTool($user, 'get_meetings', [
                'meeting_ids' => [$meetingId],
            ]);
        } catch (\Exception $e) {
            Log::warning("Failed to get Granola meeting {$meetingId}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Get the full transcript for a meeting.
     *
     * @return string|null The transcript text, or null on failure
     */
    public function getMeetingTranscript(User $user, string $meetingId): ?string
    {
        try {
            $result = $this->callTool($user, 'get_meeting_transcript', [
                'meeting_id' => $meetingId,
            ]);

            // The transcript may be returned as text content
            return $result['text'] ?? $result['transcript'] ?? json_encode($result);
        } catch (\Exception $e) {
            Log::warning("Failed to get Granola transcript for {$meetingId}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Parse Granola's XML-like meeting text into structured arrays.
     *
     * Granola returns meetings in a custom XML-like format:
     * <meeting id="..." title="..." date="...">
     *     <known_participants>...</known_participants>
     * </meeting>
     *
     * @return array<int, array{id: string, title: string, date: string, attendees: string[]}>
     */
    private function parseMeetingsText(string $text): array
    {
        $meetings = [];

        // Match each <meeting ...>...</meeting> block
        preg_match_all(
            '/<meeting\s+id="([^"]+)"\s+title="([^"]+)"\s+date="([^"]+)"[^>]*>.*?<\/meeting>/s',
            $text,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $attendees = [];

            // Extract participants from <known_participants> block
            if (preg_match('/<known_participants>\s*(.*?)\s*<\/known_participants>/s', $match[0], $partMatch)) {
                $participantText = trim($partMatch[1]);
                // Split by comma, then extract just names (before email)
                foreach (preg_split('/,\s*/', $participantText) as $participant) {
                    $participant = trim($participant);
                    // Extract name portion (before email in angle brackets or "from" clause)
                    $name = preg_replace('/\s*\(note creator\)/', '', $participant);
                    $name = preg_replace('/\s*from\s+\S+/', '', $name);
                    $name = preg_replace('/\s*<[^>]+>/', '', $name);
                    $name = trim($name);
                    if ($name !== '') {
                        $attendees[] = $name;
                    }
                }
            }

            $meetings[] = [
                'id' => $match[1],
                'title' => html_entity_decode($match[2]),
                'date' => $match[3],
                'attendees' => $attendees,
            ];
        }

        return $meetings;
    }

    /**
     * Call an MCP tool on the Granola server.
     *
     * Handles session initialization and retry on session expiry.
     * Uses per-user OAuth Bearer tokens.
     */
    public function callTool(User $user, string $tool, array $arguments = []): array
    {
        $accessToken = $this->oauth->getValidToken($user);

        if (! $accessToken) {
            throw new \RuntimeException('User has no Granola connection — OAuth required');
        }

        $sessionId = $this->ensureInitialized($user, $accessToken);

        $response = $this->sendJsonRpc('tools/call', [
            'name' => $tool,
            'arguments' => $arguments,
        ], $accessToken, $sessionId);

        // Retry once if session expired (404 or specific error)
        if ($response->status() === 404 || $this->isSessionExpired($response)) {
            Cache::forget($this->sessionCacheKey($user));
            $sessionId = $this->ensureInitialized($user, $accessToken);

            $response = $this->sendJsonRpc('tools/call', [
                'name' => $tool,
                'arguments' => $arguments,
            ], $accessToken, $sessionId);
        }

        if ($response->failed()) {
            Log::error("Granola MCP call failed: {$tool}", [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 1000),
            ]);
            throw new \RuntimeException("Granola MCP call failed: {$tool} (HTTP {$response->status()})");
        }

        $json = $this->extractJsonFromResponse($response);

        return $this->parseResponse($json);
    }

    /**
     * Ensure the MCP handshake has been performed for this user.
     *
     * Returns the session ID if the server provides one, or null if sessionless.
     */
    private function ensureInitialized(User $user, string $accessToken): ?string
    {
        $cacheKey = $this->sessionCacheKey($user);

        // Cache stores either the session ID string or '__initialized__' for sessionless servers
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached === '__initialized__' ? null : $cached;
        }

        $sessionId = $this->initialize($accessToken);

        Cache::put($cacheKey, $sessionId ?? '__initialized__', self::SESSION_TTL);

        return $sessionId;
    }

    /**
     * Perform the MCP initialize handshake.
     *
     * Returns the session ID if the server provides one, or null for sessionless servers
     * (like Granola which uses per-request OAuth tokens instead of MCP sessions).
     */
    private function initialize(string $accessToken): ?string
    {
        $response = $this->sendJsonRpc('initialize', [
            'protocolVersion' => '2025-03-26',
            'capabilities' => new \stdClass,
            'clientInfo' => [
                'name' => 'solas-run',
                'version' => '1.0.0',
            ],
        ], $accessToken);

        if ($response->failed()) {
            Log::error('Granola MCP initialize failed', [
                'status' => $response->status(),
                'body' => substr($response->body(), 0, 1000),
            ]);
            throw new \RuntimeException("Granola MCP initialize failed (HTTP {$response->status()})");
        }

        // Parse the initialize response (may be SSE or JSON)
        $json = $this->extractJsonFromResponse($response);

        $serverVersion = data_get($json, 'result.protocolVersion');
        $serverName = data_get($json, 'result.serverInfo.name', 'unknown');

        Log::info("Granola MCP initialized: {$serverName} (protocol {$serverVersion})");

        // Session ID is optional — some servers (like Granola) are sessionless
        $sessionId = $response->header('MCP-Session-Id') ?? $response->header('mcp-session-id');

        // Send initialized notification
        $this->sendNotification('notifications/initialized', [], $accessToken, $sessionId);

        return $sessionId;
    }

    /**
     * Send a JSON-RPC 2.0 request to the Granola MCP server.
     */
    private function sendJsonRpc(string $method, array $params, string $accessToken, ?string $sessionId = null): \Illuminate\Http\Client\Response
    {
        $this->requestId++;

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json, text/event-stream',
            'Authorization' => "Bearer {$accessToken}",
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
    private function sendNotification(string $method, array $params, string $accessToken, ?string $sessionId = null): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json, text/event-stream',
            'Authorization' => "Bearer {$accessToken}",
        ];

        if ($sessionId) {
            $headers['MCP-Session-Id'] = $sessionId;
        }

        Http::withHeaders($headers)
            ->timeout($this->timeout)
            ->post($this->url, [
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => $params,
            ]);
    }

    /**
     * Extract JSON from response, handling both direct JSON and SSE formats.
     *
     * MCP Streamable HTTP transport may return either:
     * - Content-Type: application/json → standard JSON body
     * - Content-Type: text/event-stream → SSE with "data: {...}" lines
     *
     * @return array Decoded JSON
     */
    private function extractJsonFromResponse(\Illuminate\Http\Client\Response $response): array
    {
        $contentType = $response->header('Content-Type') ?? '';

        // Standard JSON response
        if (str_contains($contentType, 'application/json')) {
            return $response->json() ?? [];
        }

        // SSE response — extract the last JSON-RPC message from "data:" lines
        if (str_contains($contentType, 'text/event-stream')) {
            $body = $response->body();
            $lastJson = null;

            foreach (explode("\n", $body) as $line) {
                $line = trim($line);
                if (str_starts_with($line, 'data:')) {
                    $data = trim(substr($line, 5));
                    if ($data !== '' && $data !== '[DONE]') {
                        $decoded = json_decode($data, true);
                        if ($decoded !== null) {
                            $lastJson = $decoded;
                        }
                    }
                }
            }

            return $lastJson ?? [];
        }

        // Fallback: try parsing as JSON anyway
        return $response->json() ?? [];
    }

    /**
     * Parse the JSON-RPC response, extracting tool result content.
     */
    private function parseResponse(array $json): array
    {
        if (isset($json['error'])) {
            throw new \RuntimeException(
                "Granola MCP error: {$json['error']['message']} (code: {$json['error']['code']})"
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

    /**
     * Cache key for per-user MCP session.
     */
    private function sessionCacheKey(User $user): string
    {
        return "granola:mcp:session:{$user->id}";
    }
}
