<?php

namespace App\Filament\Resources\ClubSettings\Pages;

use App\Filament\Resources\ClubSettings\ClubSettingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewClubSetting extends ViewRecord
{
    protected static string $resource = ClubSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
