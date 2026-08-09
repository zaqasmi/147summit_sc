<?php

namespace App\Filament\Resources\CapitalLiabilities\Pages;

use App\Filament\Resources\CapitalLiabilities\CapitalLiabilityResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCapitalLiability extends EditRecord
{
    protected static string $resource = CapitalLiabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
