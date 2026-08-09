<?php

namespace App\Filament\Resources\CapitalLiabilityPayments\Pages;

use App\Filament\Resources\CapitalLiabilityPayments\CapitalLiabilityPaymentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCapitalLiabilityPayment extends ViewRecord
{
    protected static string $resource = CapitalLiabilityPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
