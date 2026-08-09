<?php

namespace App\Filament\Resources\ClubSettings\Pages;

use App\Filament\Resources\ClubSettings\ClubSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditClubSetting extends EditRecord
{
    protected static string $resource = ClubSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
