<?php

namespace App\Filament\Resources\CashDeposits\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CashDepositInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('deposit_date')
                    ->date(),
                TextEntry::make('closing_source')
                    ->label('Closing method')
                    ->formatStateUsing(fn (string $state): string => $state === 'manual' ? 'Register totals' : 'Game/payment records'),
                TextEntry::make('opening_petty_cash')
                    ->label('Opening petty cash')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('manual_table_1_sale')
                    ->label('Table 1 sale')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('manual_table_2_sale')
                    ->label('Table 2 sale')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('manual_table_3_sale')
                    ->label('Table 3 sale')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('manual_table_4_sale')
                    ->label('Table 4 sale')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('manual_sales_total')
                    ->label('Total sale')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('manual_expense_total')
                    ->label('Expense')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('dues_added')
                    ->label('Customer dues')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('dues_recovered')
                    ->label('Customer dues recovered')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                RepeatableEntry::make('customer_dues')
                    ->label('Customer due items')
                    ->schema([
                        TextEntry::make('customer_name')
                            ->label('Customer'),
                        TextEntry::make('amount')
                            ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->columnSpanFull(),
                RepeatableEntry::make('customerDuePayments')
                    ->label('Customer due payments')
                    ->schema([
                        TextEntry::make('customerDue.customer_name')
                            ->label('Customer'),
                        TextEntry::make('payment_date')
                            ->date(),
                        TextEntry::make('amount')
                            ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 3,
                    ])
                    ->columnSpanFull(),
                RepeatableEntry::make('expenses')
                    ->label('Expense items')
                    ->schema([
                        TextEntry::make('category'),
                        TextEntry::make('description'),
                        TextEntry::make('amount')
                            ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                        TextEntry::make('paid_from')
                            ->label('Paid from'),
                    ])
                    ->columns([
                        'default' => 1,
                        'md' => 4,
                    ])
                    ->columnSpanFull(),
                TextEntry::make('petty_cash_kept')
                    ->label('Petty cash in counter')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('cash_to_be_collected')
                    ->label('Cash to be collected')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
                TextEntry::make('amount_collected_from_staff')
                    ->label('Actual daily collection')
                    ->formatStateUsing(fn ($state): string => 'Rs '.number_format((float) $state, 2)),
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
