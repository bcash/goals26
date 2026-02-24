<?php

namespace App\Filament\Resources\MeetingDoneItemResource\Pages;

use App\Filament\Resources\MeetingDoneItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMeetingDoneItems extends ListRecords
{
    protected static string $resource = MeetingDoneItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
