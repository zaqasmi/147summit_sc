<?php

namespace App\Filament\Resources\MonthlyCommissions\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MonthlyCommissionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('staff.name')
                    ->label('Staff'),
                TextEntry::make('month')
                    ->date(),
                TextEntry::make('cash_collected')
                    ->numeric(),
                TextEntry::make('expense_total')
                    ->numeric(),
                TextEntry::make('net_profit')
                    ->numeric(),
                TextEntry::make('commission_rate')
                    ->numeric(),
                TextEntry::make('commission_amount')
                    ->numeric(),
                TextEntry::make('carried_forward_from_previous')
                    ->numeric(),
                TextEntry::make('advances_deducted')
                    ->numeric(),
                TextEntry::make('paid_amount')
                    ->numeric(),
                TextEntry::make('balance_due')
                    ->numeric(),
                TextEntry::make('generated_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
