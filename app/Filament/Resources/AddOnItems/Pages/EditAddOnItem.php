<?php

namespace App\Filament\Resources\AddOnItems\Pages;

use App\Filament\Resources\AddOnItems\AddOnItemResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAddOnItem extends EditRecord
{
    protected static string $resource = AddOnItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
