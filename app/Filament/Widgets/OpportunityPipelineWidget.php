<?php

namespace App\Filament\Widgets;

use App\Models\DeferredItem;
use App\Models\OpportunityPipeline;

class OpportunityPipelineWidget extends BaseWidget
{
    protected static ?int $sort = 8;
    protected int | string | array $columnSpan = 1;
    protected static string $view = 'filament.widgets.opportunity-pipeline-widget';

    public function getViewData(): array
    {
        $opportunities = OpportunityPipeline::whereNotIn('stage', ['closed-won', 'closed-lost'])
            ->get();

        $totalValue = $opportunities->sum('estimated_value');
        $weightedValue = $opportunities->sum(fn ($o) => ($o->estimated_value ?? 0) * ($o->probability_percent / 100));

        $byStage = $opportunities->groupBy('stage')
            ->map(fn ($group) => [
                'count' => $group->count(),
                'value' => $group->sum('estimated_value'),
                'weighted' => $group->sum(fn ($o) => ($o->estimated_value ?? 0) * ($o->probability_percent / 100)),
            ]);

        $actionsThisWeek = $opportunities
            ->filter(fn ($o) => $o->next_action_date !== null)
            ->filter(fn ($o) => $o->next_action_date <= today()->addDays(7))
            ->sortBy('next_action_date');

        $overdueItems = DeferredItem::where(function ($q) {
            $q->where('status', 'scheduled')
                ->where('revisit_date', '<=', today());
        })->orWhere(function ($q) {
            $q->where('status', 'someday')
                ->where(function ($inner) {
                    $inner->whereNull('last_reviewed_at')
                        ->orWhere('last_reviewed_at', '<=', now()->subDays(30));
                });
        })->count();

        $staleHighValue = DeferredItem::whereNotIn('opportunity_type', ['none', 'personal-goal'])
            ->whereIn('status', ['someday', 'scheduled', 'in-review', 'promoted'])
            ->where(fn ($q) =>
                $q->whereNull('last_reviewed_at')
                    ->orWhere('last_reviewed_at', '<', now()->subDays(30))
            )
            ->orderByDesc('estimated_value')
            ->limit(10)
            ->count();

        return [
            'weightedValue' => $weightedValue,
            'totalValue' => $totalValue,
            'byStage' => $byStage,
            'actionsThisWeek' => $actionsThisWeek,
            'overdueItems' => $overdueItems,
            'staleHighValue' => $staleHighValue,
        ];
    }
}
