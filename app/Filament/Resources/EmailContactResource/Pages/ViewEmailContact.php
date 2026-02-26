<?php

namespace App\Filament\Resources\EmailContactResource\Pages;

use App\Filament\Resources\EmailContactResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewEmailContact extends ViewRecord
{
    protected static string $resource = EmailContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
