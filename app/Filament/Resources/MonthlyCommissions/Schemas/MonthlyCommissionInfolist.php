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
                    ->date('M Y'),
                TextEntry::make('commission_rate')
                    ->label('Rate')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2).'%'),
                TextEntry::make('commission_amount')
                    ->label('Monthly payment')
                    ->formatStateUsing(fn ($state): string => self::money($state)),
                TextEntry::make('total_paid')
                    ->label('Paid against month')
                    ->formatStateUsing(fn ($state): string => self::money($state)),
                TextEntry::make('monthly_remaining')
                    ->label('Monthly remaining')
                    ->formatStateUsing(fn ($state): string => self::money($state)),
                TextEntry::make('carried_forward_from_previous')
                    ->label('Previous balance')
                    ->formatStateUsing(fn ($state): string => self::money($state)),
                TextEntry::make('total_payable')
                    ->label('Overall payable')
                    ->formatStateUsing(fn ($state): string => self::money($state)),
                TextEntry::make('balance_due')
                    ->label('Overall remaining')
                    ->formatStateUsing(fn ($state): string => self::money($state)),
                TextEntry::make('balance_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Due' ? 'warning' : 'success'),
                TextEntry::make('advances_deducted')
                    ->label('Ledger paid')
                    ->formatStateUsing(fn ($state): string => self::money($state)),
                TextEntry::make('paid_amount')
                    ->label('Manual paid')
                    ->formatStateUsing(fn ($state): string => self::money($state)),
                TextEntry::make('cash_collected')
                    ->formatStateUsing(fn ($state): string => self::money($state)),
                TextEntry::make('expense_total')
                    ->formatStateUsing(fn ($state): string => self::money($state)),
                TextEntry::make('net_profit')
                    ->formatStateUsing(fn ($state): string => self::money($state)),
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

    private static function money(float|int|string|null $amount): string
    {
        return 'Rs '.number_format((float) $amount, 2);
    }
}
