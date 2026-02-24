<?php

namespace App\Filament\Resources\MeetingAgendaResource\Pages;

use App\Filament\Resources\MeetingAgendaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMeetingAgenda extends ViewRecord
{
    protected static string $resource = MeetingAgendaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
