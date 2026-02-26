<?php

namespace App\Filament\Resources\EmailConversationResource\Pages;

use App\Filament\Resources\EmailConversationResource;
use App\Models\EmailConversation;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListEmailConversations extends ListRecords
{
    protected static string $resource = EmailConversationResource::class;

    public function getTabs(): array
    {
        $userId = auth()->id();
        $userEmail = auth()->user()->email;

        return [
            'all' => Tab::make('All'),

            'unassigned' => Tab::make('Unassigned')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', 'active')
                    ->whereNull('assigned_to_name'))
                ->badge(EmailConversation::query()
                    ->where('user_id', $userId)
                    ->where('status', 'active')
                    ->whereNull('assigned_to_name')
                    ->count())
                ->badgeColor('danger'),

            'mine' => Tab::make('Mine')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', 'active')
                    ->where('assigned_to_email', $userEmail))
                ->badge(EmailConversation::query()
                    ->where('user_id', $userId)
                    ->where('status', 'active')
                    ->where('assigned_to_email', $userEmail)
                    ->count())
                ->badgeColor('primary'),

            'assigned' => Tab::make('Assigned')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', 'active')
                    ->whereNotNull('assigned_to_name')),

            'closed' => Tab::make('Closed')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', 'closed')),

            'spam' => Tab::make('Spam')
                ->modifyQueryUsing(fn (Builder $query) => $query
                    ->where('status', 'spam')),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'mine';
    }
}
