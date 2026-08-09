<?php

namespace App\Filament\Resources\TournamentMatchFrames\Pages;

use App\Filament\Resources\TournamentMatchFrames\TournamentMatchFrameResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewTournamentMatchFrame extends ViewRecord
{
    protected static string $resource = TournamentMatchFrameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
