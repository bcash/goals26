<?php

namespace App\Filament\Resources\ClientMeetingResource\Pages;

use App\Filament\Resources\ClientMeetingResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClientMeeting extends ViewRecord
{
    protected static string $resource = ClientMeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
