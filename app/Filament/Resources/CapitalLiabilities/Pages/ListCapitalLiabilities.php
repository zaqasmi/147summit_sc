<?php

namespace App\Filament\Resources\CapitalLiabilities\Pages;

use App\Filament\Concerns\ShowsCashBankBalances;
use App\Filament\Resources\CapitalLiabilities\CapitalLiabilityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCapitalLiabilities extends ListRecords
{
    use ShowsCashBankBalances;

    protected static string $resource = CapitalLiabilityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
