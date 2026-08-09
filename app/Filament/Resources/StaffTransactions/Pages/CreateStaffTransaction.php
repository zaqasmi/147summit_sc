<?php

namespace App\Filament\Resources\StaffTransactions\Pages;

use App\Filament\Resources\StaffTransactions\StaffTransactionResource;
use App\Support\StaffTransactionCreator;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateStaffTransaction extends CreateRecord
{
    protected static string $resource = StaffTransactionResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return StaffTransactionCreator::create($data);
    }
}
