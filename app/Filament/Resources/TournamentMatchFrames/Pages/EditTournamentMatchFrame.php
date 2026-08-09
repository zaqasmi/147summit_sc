<?php

namespace App\Filament\Resources\TournamentMatchFrames\Pages;

use App\Filament\Resources\TournamentMatchFrames\TournamentMatchFrameResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditTournamentMatchFrame extends EditRecord
{
    protected static string $resource = TournamentMatchFrameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
