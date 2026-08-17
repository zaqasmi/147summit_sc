<?php

namespace App\Filament\Resources\CustomerDues\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CustomerDueInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('customer_name')
                    ->label('Customer'),
                TextEntry::make('phone')
                    ->placeholder('-'),
                TextEntry::make('opening_balance')
                    ->label('Opening balance')
                    ->formatStateUsing(fn ($state): string => self::money($state)),
                TextEntry::make('total_charged')
                    ->label('Dues added')
                    ->formatStateUsing(fn ($state): string => self::money($state)),
                TextEntry::make('total_paid')
                    ->label('Paid')
                    ->formatStateUsing(fn ($state): string => self::money($state)),
                TextEntry::make('total_discounted')
                    ->label('Discounted')
                    ->formatStateUsing(fn ($state): string => self::money($state)),
                TextEntry::make('balance_due')
                    ->label('Balance due')
                    ->formatStateUsing(fn ($state): string => self::money($state)),
                TextEntry::make('notes')
                    ->placeholder('-')
                    ->columnSpanFull(),
                RepeatableEntry::make('charges')
                    ->label('Due charges')
                    ->schema([
                        TextEntry::make('charge_date')
                            ->date(),
                        TextEntry::make('cashDeposit.deposit_date')
                            ->label('Daily closing')
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('amount')
                            ->label('Amount paid')
                            ->formatStateUsing(fn ($state): string => self::money($state)),
                        TextEntry::make('discount_amount')
                            ->label('Discount')
                            ->formatStateUsing(fn ($state): string => self::money($state)),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 4,
                    ])
                    ->columnSpanFull(),
                RepeatableEntry::make('payments')
                    ->label('Payments')
                    ->schema([
                        TextEntry::make('payment_date')
                            ->date(),
                        TextEntry::make('cashDeposit.deposit_date')
                            ->label('Daily closing')
                            ->date()
                            ->placeholder('-'),
                        TextEntry::make('amount')
                            ->formatStateUsing(fn ($state): string => self::money($state)),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 3,
                    ])
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
