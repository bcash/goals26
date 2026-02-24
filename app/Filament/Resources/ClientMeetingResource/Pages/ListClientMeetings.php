<?php

namespace App\Filament\Resources\ClientMeetingResource\Pages;

use App\Filament\Resources\ClientMeetingResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;

class ListClientMeetings extends ListRecords
{
    protected static string $resource = ClientMeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncGranola')
                ->label('Sync from Granola')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    if (class_exists(\App\Services\GranolaSyncService::class)) {
                        $synced = app(\App\Services\GranolaSyncService::class)
                            ->syncRecent(limit: 10);

                        \Filament\Notifications\Notification::make()
                            ->title(count($synced) . ' meeting(s) synced from Granola')
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Granola sync service not yet available')
                            ->warning()
                            ->send();
                    }
                }),
            Actions\CreateAction::make(),
        ];
    }
}
