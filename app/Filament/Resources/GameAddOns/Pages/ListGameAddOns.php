<?php

namespace App\Filament\Resources\GameAddOns\Pages;

use App\Filament\Resources\GameAddOns\GameAddOnResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGameAddOns extends ListRecords
{
    protected static string $resource = GameAddOnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
