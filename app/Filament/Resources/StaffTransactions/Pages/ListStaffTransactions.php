<?php

namespace App\Filament\Resources\StaffTransactions\Pages;

use App\Filament\Resources\StaffTransactions\StaffTransactionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListStaffTransactions extends ListRecords
{
    protected static string $resource = StaffTransactionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
