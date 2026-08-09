<?php

namespace App\Filament\Resources\CustomerDues\Pages;

use App\Filament\Resources\CustomerDues\CustomerDueResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCustomerDue extends ViewRecord
{
    protected static string $resource = CustomerDueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
