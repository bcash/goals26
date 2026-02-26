<?php

namespace App\Filament\Widgets;

use App\Models\EmailConversation;

class RecentConversationsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    protected string $view = 'filament.widgets.recent-conversations-widget';

    public function getViewData(): array
    {
        $conversations = EmailConversation::query()
            ->where(function ($query) {
                $query->where('needs_review', true)
                    ->orWhere(function ($q) {
                        $q->where('status', 'active')
                            ->where('last_message_at', '>=', now()->subDays(3));
                    });
            })
            ->with('contact')
            ->orderByDesc('last_message_at')
            ->limit(5)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'subject' => $c->subject,
                'contact_name' => $c->contact?->fullName() ?? 'Unknown',
                'status' => $c->status,
                'last_message_at' => $c->last_message_at?->diffForHumans(),
                'url' => \App\Filament\Resources\EmailConversationResource::getUrl('view', ['record' => $c]),
            ])
            ->toArray();

        return compact('conversations');
    }
}
