<?php

namespace App\Filament\Resources\TournamentPlayers\Pages;

use App\Filament\Resources\TournamentPlayers\TournamentPlayerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTournamentPlayers extends ListRecords
{
    protected static string $resource = TournamentPlayerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
