<?php

namespace App\Filament\Resources\CustomerDues\Pages;

use App\Filament\Resources\CustomerDues\CustomerDueResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomerDue extends CreateRecord
{
    protected static string $resource = CustomerDueResource::class;
}
