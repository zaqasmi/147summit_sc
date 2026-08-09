<?php

namespace App\Filament\Resources\CustomerDues\Pages;

use App\Filament\Resources\CustomerDues\CustomerDueResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomerDues extends ListRecords
{
    protected static string $resource = CustomerDueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Export Customer Dues PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn (): string => route('customer-dues.export-pdf')),
            CreateAction::make(),
        ];
    }
}
