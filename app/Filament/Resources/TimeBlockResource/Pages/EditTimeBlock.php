<?php

namespace App\Filament\Resources\TimeBlockResource\Pages;

use App\Filament\Resources\TimeBlockResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTimeBlock extends EditRecord
{
    protected static string $resource = TimeBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
