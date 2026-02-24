<?php

namespace App\Filament\Resources\DailyPlanResource\Pages;

use App\Filament\Resources\DailyPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailyPlan extends EditRecord
{
    protected static string $resource = DailyPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
