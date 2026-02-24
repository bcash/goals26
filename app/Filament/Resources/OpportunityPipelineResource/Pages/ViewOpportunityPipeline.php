<?php

namespace App\Filament\Resources\OpportunityPipelineResource\Pages;

use App\Filament\Resources\OpportunityPipelineResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOpportunityPipeline extends ViewRecord
{
    protected static string $resource = OpportunityPipelineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
