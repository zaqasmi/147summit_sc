<?php

namespace App\Filament\Resources\TournamentPlayers\Pages;

use App\Filament\Resources\TournamentPlayers\TournamentPlayerResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTournamentPlayer extends EditRecord
{
    protected static string $resource = TournamentPlayerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
