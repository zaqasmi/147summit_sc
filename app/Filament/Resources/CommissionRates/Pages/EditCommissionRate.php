<?php

namespace App\Filament\Resources\CommissionRates\Pages;

use App\Filament\Resources\CommissionRates\CommissionRateResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCommissionRate extends EditRecord
{
    protected static string $resource = CommissionRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
