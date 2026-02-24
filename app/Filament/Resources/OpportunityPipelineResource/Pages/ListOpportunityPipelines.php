<?php

namespace App\Filament\Resources\OpportunityPipelineResource\Pages;

use App\Filament\Resources\OpportunityPipelineResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOpportunityPipelines extends ListRecords
{
    protected static string $resource = OpportunityPipelineResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
