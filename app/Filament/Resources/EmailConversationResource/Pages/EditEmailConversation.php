<?php

namespace App\Filament\Resources\EmailConversationResource\Pages;

use App\Filament\Resources\EmailConversationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailConversation extends EditRecord
{
    protected static string $resource = EmailConversationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
