<?php

namespace App\Filament\Resources\StaffTransactions\Pages;

use App\Filament\Resources\StaffTransactions\StaffTransactionResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStaffTransaction extends ViewRecord
{
    protected static string $resource = StaffTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
