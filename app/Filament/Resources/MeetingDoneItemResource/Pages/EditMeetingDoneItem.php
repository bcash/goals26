<?php

namespace App\Filament\Resources\MeetingDoneItemResource\Pages;

use App\Filament\Resources\MeetingDoneItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMeetingDoneItem extends EditRecord
{
    protected static string $resource = MeetingDoneItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
