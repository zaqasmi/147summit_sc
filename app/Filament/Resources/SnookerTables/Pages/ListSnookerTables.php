<?php

namespace App\Filament\Resources\SnookerTables\Pages;

use App\Filament\Resources\SnookerTables\SnookerTableResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSnookerTables extends ListRecords
{
    protected static string $resource = SnookerTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
