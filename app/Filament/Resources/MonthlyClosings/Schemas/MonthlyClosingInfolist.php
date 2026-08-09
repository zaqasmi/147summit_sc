<?php

namespace App\Filament\Resources\MonthlyClosings\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class MonthlyClosingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('month')
                    ->date('F Y'),
                TextEntry::make('status_label')
                    ->label('Status'),
                TextEntry::make('rent_total')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('rent_paid_amount')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('rent_paid_from_label')
                    ->label('Rent paid from'),
                TextEntry::make('construction_deduction_amount')
                    ->label('Construction deduction')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('construction_received_amount')
                    ->label('Saved in other account')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('construction_balance')
                    ->label('Construction recovery balance')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                IconEntry::make('liabilities_verified')
                    ->boolean(),
                TextEntry::make('net_profit')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('commission_amount')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('closed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('closedBy.name')
                    ->label('Closed by')
                    ->placeholder('-'),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
            ]);
    }
}
