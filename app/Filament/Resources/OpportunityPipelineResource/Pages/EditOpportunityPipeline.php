<?php

namespace App\Filament\Resources\OpportunityPipelineResource\Pages;

use App\Filament\Resources\OpportunityPipelineResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOpportunityPipeline extends EditRecord
{
    protected static string $resource = OpportunityPipelineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
