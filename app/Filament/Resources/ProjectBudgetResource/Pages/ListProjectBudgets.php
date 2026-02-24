<?php

namespace App\Filament\Resources\ProjectBudgetResource\Pages;

use App\Filament\Resources\ProjectBudgetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProjectBudgets extends ListRecords
{
    protected static string $resource = ProjectBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
