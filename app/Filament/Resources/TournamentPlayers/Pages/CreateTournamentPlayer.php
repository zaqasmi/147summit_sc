<?php

namespace App\Filament\Resources\TournamentPlayers\Pages;

use App\Filament\Resources\TournamentPlayers\TournamentPlayerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTournamentPlayer extends CreateRecord
{
    protected static string $resource = TournamentPlayerResource::class;
}
