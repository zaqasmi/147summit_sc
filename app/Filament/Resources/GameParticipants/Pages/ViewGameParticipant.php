<?php

namespace App\Filament\Resources\GameParticipants\Pages;

use App\Filament\Resources\GameParticipants\GameParticipantResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewGameParticipant extends ViewRecord
{
    protected static string $resource = GameParticipantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
