<?php

namespace App\Filament\Resources\AddOnItems\Pages;

use App\Filament\Resources\AddOnItems\AddOnItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAddOnItems extends ListRecords
{
    protected static string $resource = AddOnItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
