<?php

namespace App\Filament\Resources\LifeAreaResource\Pages;

use App\Filament\Resources\LifeAreaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLifeAreas extends ListRecords
{
    protected static string $resource = LifeAreaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
