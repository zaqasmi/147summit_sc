<?php

namespace App\Filament\Resources\CapitalLiabilityPayments\Pages;

use App\Filament\Resources\CapitalLiabilityPayments\CapitalLiabilityPaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCapitalLiabilityPayments extends ListRecords
{
    protected static string $resource = CapitalLiabilityPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
