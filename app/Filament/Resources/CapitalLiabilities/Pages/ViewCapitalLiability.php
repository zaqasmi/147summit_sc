<?php

namespace App\Filament\Resources\CapitalLiabilities\Pages;

use App\Filament\Resources\CapitalLiabilities\CapitalLiabilityResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCapitalLiability extends ViewRecord
{
    protected static string $resource = CapitalLiabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
