<?php

namespace App\Filament\Resources\EmailContactResource\Pages;

use App\Filament\Resources\EmailContactResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailContact extends EditRecord
{
    protected static string $resource = EmailContactResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
