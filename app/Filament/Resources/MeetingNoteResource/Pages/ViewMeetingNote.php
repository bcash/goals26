<?php

namespace App\Filament\Resources\MeetingNoteResource\Pages;

use App\Filament\Resources\MeetingNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewMeetingNote extends ViewRecord
{
    protected static string $resource = MeetingNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
