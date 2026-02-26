<?php

namespace App\Filament\Resources\MeetingNoteResource\Pages;

use App\Filament\Resources\MeetingNoteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMeetingNote extends EditRecord
{
    protected static string $resource = MeetingNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
