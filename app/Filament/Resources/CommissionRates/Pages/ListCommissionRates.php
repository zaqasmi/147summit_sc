<?php

namespace App\Filament\Resources\CommissionRates\Pages;

use App\Filament\Resources\CommissionRates\CommissionRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommissionRates extends ListRecords
{
    protected static string $resource = CommissionRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
