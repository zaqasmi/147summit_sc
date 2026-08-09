<?php

namespace App\Filament\Resources\SnookerTables\Pages;

use App\Filament\Resources\SnookerTables\SnookerTableResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSnookerTable extends ViewRecord
{
    protected static string $resource = SnookerTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
