<?php

namespace App\Filament\Resources\CostEntryResource\Pages;

use App\Filament\Resources\CostEntryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCostEntries extends ListRecords
{
    protected static string $resource = CostEntryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
