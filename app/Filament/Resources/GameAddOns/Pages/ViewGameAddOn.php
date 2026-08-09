<?php

namespace App\Filament\Resources\GameAddOns\Pages;

use App\Filament\Resources\GameAddOns\GameAddOnResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewGameAddOn extends ViewRecord
{
    protected static string $resource = GameAddOnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
