<?php

namespace App\Filament\Resources\DeferredItemResource\Pages;

use App\Filament\Resources\DeferredItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeferredItem extends EditRecord
{
    protected static string $resource = DeferredItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
