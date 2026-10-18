<?php

namespace App\Filament\Resources\TournamentDrawSettings\Pages;

use App\Filament\Resources\TournamentDrawSettings\TournamentDrawSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTournamentDrawSetting extends EditRecord
{
    protected static string $resource = TournamentDrawSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
