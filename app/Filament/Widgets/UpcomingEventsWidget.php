<?php

namespace App\Filament\Widgets;

use App\Models\CalendarEvent;
use Carbon\Carbon;

class UpcomingEventsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected string $view = 'filament.widgets.upcoming-events-widget';

    public function getViewData(): array
    {
        $eventGroups = CalendarEvent::upcoming()
            ->where('start_at', '<=', now()->addDays(3))
            ->limit(10)
            ->get()
            ->groupBy(fn ($event) => $event->start_at->format('Y-m-d'))
            ->map(fn ($events, $date) => [
                'date' => Carbon::parse($date)->format('l, M j'),
                'is_today' => Carbon::parse($date)->isToday(),
                'events' => $events->map(fn ($e) => [
                    'id' => $e->id,
                    'title' => $e->title,
                    'time' => $e->all_day ? 'All day' : $e->start_at->format('g:i A').' - '.$e->end_at->format('g:i A'),
                    'event_type' => $e->event_type,
                    'attendee_count' => is_array($e->attendees) ? count($e->attendees) : 0,
                    'has_agenda' => $e->hasAgenda(),
                    'duration' => $e->duration(),
                ])->toArray(),
            ])
            ->toArray();

        return compact('eventGroups');
    }
}
