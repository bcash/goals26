<?php

namespace App\Mcp\Tools;

use App\Models\MeetingNote;
use App\Models\User;
use App\Services\GranolaMcpClient;
use App\Services\GranolaOAuthService;
use App\Services\GranolaSyncService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ImportGranolaMeeting extends Tool
{
    protected string $name = 'import-granola-meeting';

    protected string $description = 'Import a specific meeting from Granola into Solas Rún as a MeetingNote record. Downloads notes and transcript, then runs AI intelligence extraction. Use query-granola-meetings first to find meeting IDs.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'meeting_id' => $schema->string()
                ->description('The Granola meeting ID to import')
                ->required(),
        ];
    }

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'meeting_id' => 'required|string|max:255',
        ]);

        $user = User::withoutGlobalScopes()->first();

        if (! $user) {
            return Response::json(['error' => 'No user found']);
        }

        $oauth = app(GranolaOAuthService::class);

        if (! $oauth->isConnected($user)) {
            return Response::json([
                'error' => 'Granola not connected',
                'message' => 'The user has not connected their Granola account.',
            ]);
        }

        $meetingId = $validated['meeting_id'];

        // Check if already imported
        $existing = MeetingNote::withoutGlobalScopes()
            ->where('granola_meeting_id', $meetingId)
            ->first();

        if ($existing) {
            return Response::json([
                'status' => 'already_imported',
                'meeting_id' => $existing->id,
                'title' => $existing->title,
                'message' => 'This meeting has already been imported.',
            ]);
        }

        try {
            // Fetch meeting data from Granola to get metadata
            $client = app(GranolaMcpClient::class);
            $meetingData = $client->getMeeting($user, $meetingId);

            if (! $meetingData) {
                return Response::json([
                    'error' => 'Meeting not found',
                    'message' => "Could not fetch meeting {$meetingId} from Granola.",
                ]);
            }

            // Import via sync service
            $syncService = app(GranolaSyncService::class);
            $meeting = $syncService->importMeeting($user, array_merge($meetingData, [
                'id' => $meetingId,
            ]));

            return Response::json([
                'status' => 'imported',
                'meeting_id' => $meeting->id,
                'title' => $meeting->title,
                'date' => $meeting->meeting_date?->toDateString(),
                'has_transcript' => ! empty($meeting->transcript),
                'transcription_status' => $meeting->transcription_status,
            ]);
        } catch (\Exception $e) {
            return Response::json([
                'error' => 'Import failed',
                'message' => $e->getMessage(),
            ]);
        }
    }
}
