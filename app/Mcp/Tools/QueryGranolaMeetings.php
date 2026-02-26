<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Services\GranolaMcpClient;
use App\Services\GranolaOAuthService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class QueryGranolaMeetings extends Tool
{
    protected string $name = 'query-granola-meetings';

    protected string $description = 'Query meetings from the user\'s connected Granola account. Returns meeting summaries with IDs, titles, dates, and attendees. Use without a query to list recent meetings, or provide a search query to filter.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()
                ->description('Optional search query to filter meetings by title or content')
                ->nullable(),
            'limit' => $schema->integer()
                ->description('Maximum number of meetings to return (default: 25)')
                ->nullable(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:500',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $user = User::withoutGlobalScopes()->first();

        if (! $user) {
            return Response::json(['error' => 'No user found']);
        }

        $oauth = app(GranolaOAuthService::class);

        if (! $oauth->isConnected($user)) {
            return Response::json([
                'error' => 'Granola not connected',
                'message' => 'The user has not connected their Granola account. They can do so from Client Meetings > Connect Granola.',
            ]);
        }

        $client = app(GranolaMcpClient::class);
        $limit = $validated['limit'] ?? 25;
        $query = $validated['query'] ?? null;

        try {
            $meetings = $query
                ? $client->queryMeetings($user, $query, $limit)
                : $client->listMeetings($user, $limit);

            return Response::json([
                'meetings' => $meetings['meetings'] ?? $meetings,
                'count' => count($meetings['meetings'] ?? $meetings),
            ]);
        } catch (\Exception $e) {
            return Response::json([
                'error' => 'Failed to query Granola meetings',
                'message' => $e->getMessage(),
            ]);
        }
    }
}
