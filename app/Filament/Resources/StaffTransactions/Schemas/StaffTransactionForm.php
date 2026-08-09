<?php

namespace App\Filament\Resources\StaffTransactions\Schemas;

use App\Models\StaffTransaction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class StaffTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Checkbox::make('split_between_all_staff')
                    ->label('All active staff')
                    ->helperText('Create one transaction per active staff member and split this amount equally.')
                    ->live()
                    ->visible(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                Select::make('staff_id')
                    ->relationship('staff', 'name')
                    ->searchable()
                    ->preload()
                    ->required(fn (Get $get): bool => ! (bool) $get('split_between_all_staff'))
                    ->hidden(fn (Get $get, string $operation): bool => $operation === 'create' && (bool) $get('split_between_all_staff'))
                    ->dehydrated(fn (Get $get): bool => ! (bool) $get('split_between_all_staff')),
                DatePicker::make('transaction_date')
                    ->default(today())
                    ->required(),
                DatePicker::make('commission_month')
                    ->default(today()->startOfMonth()),
                Select::make('type')
                    ->options([
                        'advance' => 'Advance paid',
                        'payout' => 'Commission payout',
                        'adjustment' => 'Adjustment',
                    ])
                    ->required()
                    ->default('advance'),
                Select::make('paid_from')
                    ->label('Paid from')
                    ->options(StaffTransaction::paidFromOptions())
                    ->required()
                    ->default('cash')
                    ->helperText('Cash reduces cash pending bank deposit. Bank, EasyPaisa, and other bank create a bank ledger entry.'),
                TextInput::make('amount')
                    ->prefix('Rs')
                    ->required()
                    ->numeric(),
                TextInput::make('description'),
            ]);
    }
}
