<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Concerns\ShowsCashBankBalances;
use App\Filament\Resources\Payments\PaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    use ShowsCashBankBalances;

    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
