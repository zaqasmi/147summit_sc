<?php

namespace App\Filament\Resources\ClubSettings\Pages;

use App\Filament\Resources\ClubSettings\ClubSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClubSettings extends ListRecords
{
    protected static string $resource = ClubSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
