<?php

namespace App\Filament\Resources\SnookerTables\Pages;

use App\Filament\Resources\SnookerTables\SnookerTableResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSnookerTable extends EditRecord
{
    protected static string $resource = SnookerTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
