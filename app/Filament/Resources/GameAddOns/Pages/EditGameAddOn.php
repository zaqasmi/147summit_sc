<?php

namespace App\Filament\Resources\GameAddOns\Pages;

use App\Filament\Resources\GameAddOns\GameAddOnResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditGameAddOn extends EditRecord
{
    protected static string $resource = GameAddOnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
