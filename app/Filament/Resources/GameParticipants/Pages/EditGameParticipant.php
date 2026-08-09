<?php

namespace App\Filament\Resources\GameParticipants\Pages;

use App\Filament\Resources\GameParticipants\GameParticipantResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditGameParticipant extends EditRecord
{
    protected static string $resource = GameParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
