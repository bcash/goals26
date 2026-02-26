<?php

namespace App\Filament\Widgets;

use App\Models\MeetingDoneItem;
use App\Models\Task;

class DoneDeliveredWidget extends BaseWidget
{
    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 1;

    protected string $view = 'filament.widgets.done-delivered-widget';

    public function getViewData(): array
    {
        $recentDoneItems = MeetingDoneItem::with('meetingNote')
            ->latest()
            ->limit(5)
            ->get();

        $totalValueDelivered = MeetingDoneItem::sum('value_delivered');
        $thisMonthDone = Task::where('status', 'done')
            ->whereMonth('updated_at', now()->month)
            ->count();

        return compact('recentDoneItems', 'totalValueDelivered', 'thisMonthDone');
    }
}
