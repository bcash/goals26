<?php

namespace App\Filament\Resources\DailyPlanResource\Pages;

use App\Filament\Resources\DailyPlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDailyPlan extends ViewRecord
{
    protected static string $resource = DailyPlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
