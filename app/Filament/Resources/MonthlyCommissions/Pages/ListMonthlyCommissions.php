<?php

namespace App\Filament\Resources\MonthlyCommissions\Pages;

use App\Filament\Resources\MonthlyCommissions\MonthlyCommissionResource;
use Filament\Resources\Pages\ListRecords;

class ListMonthlyCommissions extends ListRecords
{
    protected static string $resource = MonthlyCommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
