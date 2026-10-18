<?php

namespace App\Filament\Resources\TournamentDrawSettings\Pages;

use App\Filament\Resources\TournamentDrawSettings\TournamentDrawSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTournamentDrawSettings extends ListRecords
{
    protected static string $resource = TournamentDrawSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
