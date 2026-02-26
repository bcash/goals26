<?php

namespace App\Filament\Resources\MeetingNoteResource\Pages;

use App\Filament\Resources\MeetingNoteResource;
use App\Services\GranolaOAuthService;
use App\Services\GranolaSyncService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListMeetingNotes extends ListRecords
{
    protected static string $resource = MeetingNoteResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $isConnected = app(GranolaOAuthService::class)->isConnected($user);

        return [
            Action::make('connectGranola')
                ->label('Connect Granola')
                ->icon('heroicon-o-link')
                ->color('gray')
                ->url(route('granola.redirect'))
                ->visible(! $isConnected),

            Action::make('syncGranola')
                ->label('Sync from Granola')
                ->icon('heroicon-o-arrow-path')
                ->visible($isConnected)
                ->action(function () {
                    $user = auth()->user();
                    $synced = app(GranolaSyncService::class)
                        ->syncRecent(user: $user, days: 7);

                    Notification::make()
                        ->title(count($synced).' meeting(s) synced from Granola')
                        ->success()
                        ->send();
                }),

            Actions\CreateAction::make(),
        ];
    }
}
