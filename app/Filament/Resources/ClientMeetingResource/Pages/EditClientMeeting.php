<?php

namespace App\Filament\Resources\ClientMeetingResource\Pages;

use App\Filament\Resources\ClientMeetingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClientMeeting extends EditRecord
{
    protected static string $resource = ClientMeetingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
