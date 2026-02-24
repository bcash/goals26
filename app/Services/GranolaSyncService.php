<?php

namespace App\Services;

use App\Models\ClientMeeting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GranolaSyncService
{
    public function __construct(
        protected GranolaMcpClient $granola,
        protected MeetingIntelligenceService $intelligence
    ) {}

    /**
     * Sync recent meetings from Granola.
     * Finds meetings not yet imported, downloads notes + transcript,
     * and runs intelligence extraction.
     *
     * @param User $user The user to sync meetings for
     * @param int $days Number of days back to search
     * @return Collection Collection of synced ClientMeeting records
     */
    public function syncRecent(User $user, int $days = 7): Collection
    {
        $synced = collect();

        try {
            $results = $this->granola->searchMeetings([
                'limit' => 25,
            ]);

            $meetings = $results['meetings'] ?? $results;

            if (!is_array($meetings)) {
                return $synced;
            }

            foreach ($meetings as $gMeeting) {
                $granId = $gMeeting['id'] ?? $gMeeting['meeting_id'] ?? null;

                if (!$granId) {
                    continue;
                }

                // Check meeting date is within the sync window
                $meetingDate = isset($gMeeting['date'])
                    ? Carbon::parse($gMeeting['date'])
                    : now();

                if ($meetingDate->lt(now()->subDays($days))) {
                    continue;
                }

                // Skip if already synced
                if (ClientMeeting::where('granola_meeting_id', $granId)->exists()) {
                    continue;
                }

                try {
                    $imported = $this->importMeeting($user, $gMeeting);
                    $synced->push($imported);
                } catch (\Exception $e) {
                    Log::warning("Failed to import Granola meeting {$granId}: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error('Granola sync failed: ' . $e->getMessage());
        }

        return $synced;
    }

    /**
     * Import a single meeting from Granola data.
     */
    public function importMeeting(User $user, array $granolaMeeting): ClientMeeting
    {
        $granId = $granolaMeeting['id'] ?? $granolaMeeting['meeting_id'] ?? '';
        $title = $granolaMeeting['title'] ?? 'Untitled Meeting';
        $date = isset($granolaMeeting['date'])
            ? Carbon::parse($granolaMeeting['date'])
            : now();
        $attendees = $granolaMeeting['attendees'] ?? [];

        // Fetch structured notes and transcript from Granola
        $notes = $this->granola->downloadNote($granId);
        $transcript = $this->granola->downloadTranscript($granId);

        // Format the raw data
        $formattedNotes = $notes ? $this->formatNotes($notes) : null;
        $formattedTranscript = $transcript ? $this->formatTranscript($transcript) : null;

        // Create or update local meeting record
        $meeting = ClientMeeting::updateOrCreate(
            ['granola_meeting_id' => $granId],
            [
                'user_id' => $user->id,
                'title' => $title,
                'meeting_date' => $date,
                'meeting_type' => 'check-in',
                'client_type' => 'external',
                'attendees' => $attendees,
                'source' => 'granola',
                'transcript' => $formattedTranscript,
                'summary' => $formattedNotes,
                'transcription_status' => 'received',
                'transcript_received_at' => now(),
            ]
        );

        // Run AI intelligence extraction if we have a transcript
        if ($formattedTranscript) {
            $this->intelligence->analyze($meeting);
        }

        return $meeting;
    }

    /**
     * Re-sync an existing meeting (e.g. notes were updated in Granola).
     */
    public function resyncMeeting(ClientMeeting $meeting): void
    {
        if (!$meeting->granola_meeting_id) {
            throw new \InvalidArgumentException('Meeting has no Granola ID -- cannot resync.');
        }

        // Re-fetch from Granola
        $notes = $this->granola->downloadNote($meeting->granola_meeting_id);
        $transcript = $this->granola->downloadTranscript($meeting->granola_meeting_id);

        $formattedNotes = $notes ? $this->formatNotes($notes) : $meeting->summary;
        $formattedTranscript = $transcript ? $this->formatTranscript($transcript) : $meeting->transcript;

        $meeting->update([
            'transcript' => $formattedTranscript,
            'summary' => $formattedNotes,
            'transcription_status' => 'received',
            'transcript_received_at' => now(),
        ]);

        // Re-run analysis
        if ($formattedTranscript) {
            $this->intelligence->analyze($meeting);
        }
    }

    /**
     * Format a raw transcript string into a readable format.
     * Handles both string input and potential pre-formatted data.
     */
    public function formatTranscript(string $raw): string
    {
        // If it's JSON, try to parse it as transcript segments
        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            $segments = $decoded['segments'] ?? $decoded;

            if (!empty($segments) && isset($segments[0]['speaker'])) {
                return collect($segments)
                    ->map(fn ($seg) => "[{$seg['speaker']}] {$seg['text']}")
                    ->join("\n");
            }
        }

        // Already formatted or plain text -- return as-is
        return trim($raw);
    }

    /**
     * Format raw meeting notes into a readable format.
     * Handles both string input and potential pre-formatted data.
     */
    public function formatNotes(string $raw): string
    {
        // If it's JSON, try to parse it as structured notes
        $decoded = json_decode($raw, true);

        if (is_array($decoded)) {
            $panels = $decoded['panels'] ?? $decoded['sections'] ?? [];

            if (!empty($panels) && isset($panels[0]['title'])) {
                return collect($panels)
                    ->map(fn ($panel) => "## {$panel['title']}\n{$panel['content']}")
                    ->join("\n\n");
            }
        }

        // Already formatted or plain text -- return as-is
        return trim($raw);
    }
}
