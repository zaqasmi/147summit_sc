<?php

namespace App\Filament\Resources\MonthlyCommissions\Pages;

use App\Filament\Resources\MonthlyCommissions\MonthlyCommissionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMonthlyCommission extends ViewRecord
{
    protected static string $resource = MonthlyCommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
