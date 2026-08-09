<?php

namespace App\Filament\Resources\TournamentMatches\Pages;

use App\Filament\Resources\TournamentMatches\TournamentMatchResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTournamentMatch extends CreateRecord
{
    protected static string $resource = TournamentMatchResource::class;
}
