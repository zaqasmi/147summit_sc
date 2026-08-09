<?php

namespace App\Filament\Resources\TournamentMatches\Pages;

use App\Filament\Resources\TournamentMatches\TournamentMatchResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTournamentMatch extends EditRecord
{
    protected static string $resource = TournamentMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
