<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\GoogleCalendarConfig;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleCalendarSyncService
{
    public function __construct(
        protected GoogleCalendarOAuthService $oauth
    ) {}

    /**
     * Fetch the user's Google Calendar list for the selection UI.
     *
     * @return array<int, array{id: string, summary: string, primary: bool, background_color: string|null}>
     */
    public function fetchCalendars(User $user): array
    {
        $token = $this->oauth->getValidToken($user);

        if (! $token) {
            return [];
        }

        try {
            $response = Http::withToken($token)
                ->get('https://www.googleapis.com/calendar/v3/users/me/calendarList');

            if ($response->failed()) {
                Log::warning('Failed to fetch Google calendars', ['status' => $response->status()]);

                return [];
            }

            return collect($response->json('items', []))
                ->map(fn (array $cal) => [
                    'id' => $cal['id'],
                    'summary' => $cal['summary'] ?? $cal['id'],
                    'primary' => $cal['primary'] ?? false,
                    'background_color' => $cal['backgroundColor'] ?? null,
                ])
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error fetching Google calendars: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Sync events from all enabled Google calendars for the user.
     */
    public function syncEvents(User $user, int $days = 14): Collection
    {
        $synced = collect();
        $token = $this->oauth->getValidToken($user);

        if (! $token) {
            return $synced;
        }

        $configs = GoogleCalendarConfig::where('user_id', $user->id)
            ->where('sync_enabled', true)
            ->get();

        foreach ($configs as $config) {
            try {
                $events = $this->fetchEventsFromCalendar($token, $config->google_calendar_id, $days);

                foreach ($events as $event) {
                    // Skip events without attendees if configured
                    if ($config->only_with_attendees && empty($event['attendees'])) {
                        continue;
                    }

                    $imported = $this->importEvent($user, $event, $config);
                    $synced->push($imported);
                }
            } catch (\Exception $e) {
                Log::warning("Failed to sync calendar {$config->google_calendar_id}: ".$e->getMessage());
            }
        }

        return $synced;
    }

    /**
     * Fetch events from a specific Google Calendar.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchEventsFromCalendar(string $token, string $calendarId, int $days): array
    {
        $response = Http::withToken($token)
            ->get('https://www.googleapis.com/calendar/v3/calendars/'.urlencode($calendarId).'/events', [
                'timeMin' => now()->toRfc3339String(),
                'timeMax' => now()->addDays($days)->toRfc3339String(),
                'singleEvents' => 'true',
                'orderBy' => 'startTime',
                'maxResults' => 100,
            ]);

        if ($response->failed()) {
            throw new \RuntimeException("Failed to fetch events: HTTP {$response->status()}");
        }

        return $response->json('items', []);
    }

    /**
     * Import or update a single Google Calendar event.
     */
    public function importEvent(User $user, array $googleEvent, GoogleCalendarConfig $config): CalendarEvent
    {
        $eventId = $googleEvent['id'];
        $isAllDay = isset($googleEvent['start']['date']);

        $startAt = $isAllDay
            ? Carbon::parse($googleEvent['start']['date'])->startOfDay()
            : Carbon::parse($googleEvent['start']['dateTime']);

        $endAt = $isAllDay
            ? Carbon::parse($googleEvent['end']['date'])->startOfDay()
            : Carbon::parse($googleEvent['end']['dateTime']);

        $attendees = collect($googleEvent['attendees'] ?? [])
            ->map(fn (array $a) => [
                'email' => $a['email'] ?? '',
                'name' => $a['displayName'] ?? null,
                'response_status' => $a['responseStatus'] ?? 'needsAction',
            ])
            ->toArray();

        return CalendarEvent::updateOrCreate(
            ['google_event_id' => $eventId],
            [
                'user_id' => $user->id,
                'life_area_id' => $config->life_area_id,
                'google_calendar_id' => $config->google_calendar_id,
                'title' => $googleEvent['summary'] ?? 'Untitled Event',
                'description' => $googleEvent['description'] ?? null,
                'location' => $googleEvent['location'] ?? null,
                'start_at' => $startAt,
                'end_at' => $endAt,
                'all_day' => $isAllDay,
                'attendees' => $attendees,
                'organizer_email' => $googleEvent['organizer']['email'] ?? null,
                'status' => $googleEvent['status'] ?? 'confirmed',
                'event_type' => 'meeting',
                'recurrence_rule' => $googleEvent['recurrence'][0] ?? null,
                'source' => 'google',
                'synced_at' => now(),
            ]
        );
    }
}
