<?php

namespace App\Filament\Resources\OwnerCapitals\Pages;

use App\Filament\Resources\OwnerCapitals\OwnerCapitalResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOwnerCapital extends ViewRecord
{
    protected static string $resource = OwnerCapitalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
