<?php

namespace App\Filament\Resources\OwnerCapitals\Pages;

use App\Filament\Resources\OwnerCapitals\OwnerCapitalResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditOwnerCapital extends EditRecord
{
    protected static string $resource = OwnerCapitalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
