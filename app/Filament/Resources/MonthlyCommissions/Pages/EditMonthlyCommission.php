<?php

namespace App\Filament\Resources\MonthlyCommissions\Pages;

use App\Filament\Resources\MonthlyCommissions\MonthlyCommissionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMonthlyCommission extends EditRecord
{
    protected static string $resource = MonthlyCommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
