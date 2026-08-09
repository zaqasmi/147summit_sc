<?php

namespace App\Filament\Resources\StaffTransactions\Pages;

use App\Filament\Resources\StaffTransactions\StaffTransactionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditStaffTransaction extends EditRecord
{
    protected static string $resource = StaffTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
