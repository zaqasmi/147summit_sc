<?php

namespace App\Filament\Resources\CapitalLiabilityPayments\Pages;

use App\Filament\Resources\CapitalLiabilityPayments\CapitalLiabilityPaymentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCapitalLiabilityPayment extends EditRecord
{
    protected static string $resource = CapitalLiabilityPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
