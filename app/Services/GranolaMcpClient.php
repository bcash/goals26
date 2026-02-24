<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GranolaMcpClient
{
    /**
     * Search for meetings in Granola.
     *
     * @param array $filters Optional filters: query, list_id, limit, cursor
     */
    public function searchMeetings(array $filters = []): array
    {
        return $this->callMcpTool('search_meetings', array_filter([
            'query' => $filters['query'] ?? null,
            'list_id' => $filters['list_id'] ?? null,
            'limit' => $filters['limit'] ?? 25,
            'cursor' => $filters['cursor'] ?? null,
        ]));
    }

    /**
     * Download the AI-generated structured notes for a meeting.
     *
     * Returns sections/panels with metadata:
     *   section_count, bullet_count, word_count
     *
     * @return string|null The formatted meeting notes, or null on failure
     */
    public function downloadNote(string $meetingId): ?string
    {
        try {
            $result = $this->callMcpTool('download_note', [
                'meeting_id' => $meetingId,
            ]);

            // Return the raw result as a formatted string if it's an array
            if (is_array($result)) {
                $panels = $result['panels'] ?? $result['sections'] ?? [];

                if (!empty($panels)) {
                    return collect($panels)
                        ->map(fn ($panel) => "## " . ($panel['title'] ?? 'Section') . "\n" . ($panel['content'] ?? ''))
                        ->join("\n\n");
                }

                // If it's just raw text content
                return $result['text'] ?? $result['content'] ?? json_encode($result);
            }

            return is_string($result) ? $result : null;
        } catch (\Exception $e) {
            Log::warning("Failed to download Granola note for meeting {$meetingId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Download the full transcript with speaker diarization.
     *
     * Returns segments with:
     *   speaker, text, timestamp
     * Plus metadata: segment_count, duration, speaker_breakdown
     *
     * @return string|null The formatted transcript, or null on failure
     */
    public function downloadTranscript(string $meetingId): ?string
    {
        try {
            $result = $this->callMcpTool('download_transcript', [
                'meeting_id' => $meetingId,
            ]);

            if (is_array($result)) {
                $segments = $result['segments'] ?? $result;

                if (!empty($segments) && isset($segments[0]['speaker'])) {
                    return collect($segments)
                        ->map(fn ($seg) => "[{$seg['speaker']}] {$seg['text']}")
                        ->join("\n");
                }

                return $result['text'] ?? $result['content'] ?? json_encode($result);
            }

            return is_string($result) ? $result : null;
        } catch (\Exception $e) {
            Log::warning("Failed to download Granola transcript for meeting {$meetingId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Call a Granola MCP tool.
     *
     * The MCP transport layer depends on your setup:
     * - stdio (local process)
     * - HTTP/SSE (remote server)
     *
     * This implementation uses HTTP transport to a local MCP server.
     * Adjust the base URL and auth method to match your Granola MCP setup.
     */
    private function callMcpTool(string $tool, array $arguments): array
    {
        $baseUrl = config('services.granola.mcp_url', 'http://localhost:3333');

        $response = Http::baseUrl($baseUrl)
            ->timeout(30)
            ->post('/call-tool', [
                'name' => $tool,
                'arguments' => $arguments,
            ]);

        if ($response->failed()) {
            Log::error("Granola MCP call failed: {$tool}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \RuntimeException("Granola MCP call failed: {$tool} (HTTP {$response->status()})");
        }

        // Parse the MCP response format
        $contentText = $response->json('content.0.text');

        if ($contentText) {
            $decoded = json_decode($contentText, true);
            return $decoded ?? ['text' => $contentText];
        }

        return $response->json() ?? [];
    }
}
