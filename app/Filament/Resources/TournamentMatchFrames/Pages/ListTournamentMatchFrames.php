<?php

namespace App\Filament\Resources\TournamentMatchFrames\Pages;

use App\Filament\Resources\TournamentMatchFrames\TournamentMatchFrameResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTournamentMatchFrames extends ListRecords
{
    protected static string $resource = TournamentMatchFrameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
