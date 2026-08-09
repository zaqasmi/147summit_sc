<?php

namespace App\Filament\Resources\CommissionRates\Pages;

use App\Filament\Resources\CommissionRates\CommissionRateResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCommissionRate extends ViewRecord
{
    protected static string $resource = CommissionRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
