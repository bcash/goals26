<?php

namespace App\Filament\Resources\DeferredItemResource\Pages;

use App\Filament\Resources\DeferredItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDeferredItems extends ListRecords
{
    protected static string $resource = DeferredItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
