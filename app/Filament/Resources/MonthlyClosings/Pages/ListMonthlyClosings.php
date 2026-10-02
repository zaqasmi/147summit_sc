<?php

namespace App\Filament\Resources\MonthlyClosings\Pages;

use App\Filament\Concerns\ShowsCashBankBalances;
use App\Filament\Resources\MonthlyClosings\MonthlyClosingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMonthlyClosings extends ListRecords
{
    use ShowsCashBankBalances;

    protected static string $resource = MonthlyClosingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
