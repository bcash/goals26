<?php

namespace App\Filament\Resources\CostEntryResource\Pages;

use App\Filament\Resources\CostEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCostEntry extends EditRecord
{
    protected static string $resource = CostEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
