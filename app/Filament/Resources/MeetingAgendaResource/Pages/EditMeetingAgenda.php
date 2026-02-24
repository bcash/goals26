<?php

namespace App\Filament\Resources\MeetingAgendaResource\Pages;

use App\Filament\Resources\MeetingAgendaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMeetingAgenda extends EditRecord
{
    protected static string $resource = MeetingAgendaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
