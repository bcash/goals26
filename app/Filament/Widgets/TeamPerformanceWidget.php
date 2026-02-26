<?php

namespace App\Filament\Widgets;

use App\Models\EmailConversation;

class TeamPerformanceWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 1;

    protected string $view = 'filament.widgets.team-performance-widget';

    public function getViewData(): array
    {
        $sevenDaysAgo = now()->subDays(7);

        $averageQuality = EmailConversation::query()
            ->where('last_message_at', '>=', $sevenDaysAgo)
            ->whereNotNull('ai_priority_score')
            ->avg('ai_priority_score');

        $needsReviewCount = EmailConversation::query()
            ->where('needs_review', true)
            ->count();

        $weeklyConversations = EmailConversation::query()
            ->where('last_message_at', '>=', $sevenDaysAgo)
            ->count();

        return [
            'averageQuality' => $averageQuality ? round($averageQuality, 1) : null,
            'needsReviewCount' => $needsReviewCount,
            'weeklyConversations' => $weeklyConversations,
        ];
    }
}
