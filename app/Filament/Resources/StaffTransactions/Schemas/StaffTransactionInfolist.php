<?php

namespace App\Filament\Resources\StaffTransactions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class StaffTransactionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('staff.name')
                    ->label('Staff'),
                TextEntry::make('cashDeposit.deposit_date')
                    ->label('Daily closing')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('transaction_date')
                    ->date(),
                TextEntry::make('commission_month')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('type'),
                TextEntry::make('paid_from_label')
                    ->label('Paid from'),
                TextEntry::make('amount')
                    ->numeric(),
                TextEntry::make('description')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
