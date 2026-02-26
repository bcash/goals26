<?php

namespace App\Services;

use App\Models\MeetingNote;
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
     * @param  User  $user  The user to sync meetings for
     * @param  int  $days  Number of days back to search
     * @return Collection Collection of synced MeetingNote records
     */
    public function syncRecent(User $user, int $days = 7): Collection
    {
        $synced = collect();

        try {
            $results = $this->granola->listMeetings($user, 25);

            $meetings = $results['meetings'] ?? $results;

            if (! is_array($meetings)) {
                return $synced;
            }

            foreach ($meetings as $gMeeting) {
                $granId = $gMeeting['id'] ?? $gMeeting['meeting_id'] ?? null;

                if (! $granId) {
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
                if (MeetingNote::where('granola_meeting_id', $granId)->exists()) {
                    continue;
                }

                try {
                    $imported = $this->importMeeting($user, $gMeeting);
                    $synced->push($imported);
                } catch (\Exception $e) {
                    Log::warning("Failed to import Granola meeting {$granId}: ".$e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error('Granola sync failed: '.$e->getMessage());
        }

        return $synced;
    }

    /**
     * Import a single meeting from Granola data.
     */
    public function importMeeting(User $user, array $granolaMeeting): MeetingNote
    {
        $granId = $granolaMeeting['id'] ?? $granolaMeeting['meeting_id'] ?? '';
        $title = $granolaMeeting['title'] ?? 'Untitled Meeting';
        $date = isset($granolaMeeting['date'])
            ? Carbon::parse($granolaMeeting['date'])
            : now();
        $attendees = $granolaMeeting['attendees'] ?? [];

        // Fetch structured notes and transcript from Granola
        $meetingDetails = $this->granola->getMeeting($user, $granId);
        $transcript = $this->granola->getMeetingTranscript($user, $granId);

        // Format the raw data
        $formattedNotes = $meetingDetails ? $this->formatNotes($meetingDetails) : null;
        $formattedTranscript = $transcript ? $this->formatTranscript($transcript) : null;

        // Create or update local meeting record
        $meeting = MeetingNote::updateOrCreate(
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
                'transcription_status' => 'pending',
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
    public function resyncMeeting(User $user, MeetingNote $meeting): void
    {
        if (! $meeting->granola_meeting_id) {
            throw new \InvalidArgumentException('Meeting has no Granola ID -- cannot resync.');
        }

        // Re-fetch from Granola
        $meetingDetails = $this->granola->getMeeting($user, $meeting->granola_meeting_id);
        $transcript = $this->granola->getMeetingTranscript($user, $meeting->granola_meeting_id);

        $formattedNotes = $meetingDetails ? $this->formatNotes($meetingDetails) : $meeting->summary;
        $formattedTranscript = $transcript ? $this->formatTranscript($transcript) : $meeting->transcript;

        $meeting->update([
            'transcript' => $formattedTranscript,
            'summary' => $formattedNotes,
            'transcription_status' => 'pending',
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

            if (! empty($segments) && isset($segments[0]['speaker'])) {
                return collect($segments)
                    ->map(fn ($seg) => "[{$seg['speaker']}] {$seg['text']}")
                    ->join("\n");
            }
        }

        // Already formatted or plain text -- return as-is
        return trim($raw);
    }

    /**
     * Format meeting details/notes into a readable format.
     * Handles Granola's XML-like response that contains <summary> blocks.
     *
     * @param  array|string  $raw  Raw meeting details
     */
    public function formatNotes(array|string $raw): string
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (! is_array($decoded)) {
                return $this->extractSummaryFromXml($raw);
            }
            $raw = $decoded;
        }

        // Granola getMeeting returns ['text' => '<meetings_data>...<summary>...</summary>...']
        if (isset($raw['text'])) {
            return $this->extractSummaryFromXml($raw['text']);
        }

        // Extract panels/sections from structured array data
        $panels = $raw['panels'] ?? $raw['sections'] ?? [];
        if (! empty($panels) && isset($panels[0]['title'])) {
            return collect($panels)
                ->map(fn ($panel) => "## {$panel['title']}\n{$panel['content']}")
                ->join("\n\n");
        }

        if (isset($raw['content'])) {
            return trim($raw['content']);
        }

        if (isset($raw['notes'])) {
            return is_string($raw['notes']) ? trim($raw['notes']) : json_encode($raw['notes']);
        }

        return json_encode($raw);
    }

    /**
     * Extract the <summary> block from Granola's XML-like meeting text.
     * Falls back to the full text if no <summary> is found.
     */
    private function extractSummaryFromXml(string $text): string
    {
        if (preg_match('/<summary>\s*(.*?)\s*<\/summary>/s', $text, $match)) {
            return trim($match[1]);
        }

        // Strip XML tags and return as plain text
        $cleaned = preg_replace('/<[^>]+>/', '', $text);

        return trim($cleaned);
    }
}
