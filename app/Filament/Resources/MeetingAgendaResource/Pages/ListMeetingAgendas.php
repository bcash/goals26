<?php

namespace App\Filament\Resources\MeetingAgendaResource\Pages;

use App\Filament\Resources\MeetingAgendaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMeetingAgendas extends ListRecords
{
    protected static string $resource = MeetingAgendaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
