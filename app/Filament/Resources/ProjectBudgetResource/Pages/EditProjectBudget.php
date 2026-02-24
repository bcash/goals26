<?php

namespace App\Filament\Resources\ProjectBudgetResource\Pages;

use App\Filament\Resources\ProjectBudgetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProjectBudget extends EditRecord
{
    protected static string $resource = ProjectBudgetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
