<?php

namespace App\Filament\Resources\AddOnItems\Pages;

use App\Filament\Resources\AddOnItems\AddOnItemResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAddOnItem extends ViewRecord
{
    protected static string $resource = AddOnItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
