<?php

namespace App\Filament\Resources\TournamentPlayers\Pages;

use App\Filament\Resources\TournamentPlayers\TournamentPlayerResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTournamentPlayer extends ViewRecord
{
    protected static string $resource = TournamentPlayerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
