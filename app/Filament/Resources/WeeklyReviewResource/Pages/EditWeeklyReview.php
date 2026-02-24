<?php

namespace App\Filament\Resources\WeeklyReviewResource\Pages;

use App\Filament\Resources\WeeklyReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWeeklyReview extends EditRecord
{
    protected static string $resource = WeeklyReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
