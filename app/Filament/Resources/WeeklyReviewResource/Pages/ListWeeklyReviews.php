<?php

namespace App\Filament\Resources\WeeklyReviewResource\Pages;

use App\Filament\Resources\WeeklyReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWeeklyReviews extends ListRecords
{
    protected static string $resource = WeeklyReviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
