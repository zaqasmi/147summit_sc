<?php

namespace App\Filament\Resources\CustomerDues\Pages;

use App\Filament\Resources\CustomerDues\CustomerDueResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomerDue extends EditRecord
{
    protected static string $resource = CustomerDueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
