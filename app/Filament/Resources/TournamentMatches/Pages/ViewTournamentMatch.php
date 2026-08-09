<?php

namespace App\Filament\Resources\TournamentMatches\Pages;

use App\Filament\Resources\TournamentMatches\TournamentMatchResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTournamentMatch extends ViewRecord
{
    protected static string $resource = TournamentMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
