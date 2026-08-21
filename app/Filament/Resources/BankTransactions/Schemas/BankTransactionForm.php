<?php

namespace App\Filament\Resources\BankTransactions\Schemas;

use App\Models\BankTransaction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class BankTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Bank Transaction')
                    ->icon('heroicon-o-building-library')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        DatePicker::make('transaction_date')
                            ->default(today())
                            ->required(),
                        Select::make('entry_side')
                            ->label('Movement')
                            ->options([
                                'credit' => 'Credit - money in bank',
                                'debit' => 'Debit - money out of bank',
                                'cash' => 'Pending cash adjustment - no bank effect',
                            ])
                            ->default('credit')
                            ->live()
                            ->required()
                            ->afterStateHydrated(function (Select $component, ?BankTransaction $record): void {
                                if (! $record) {
                                    return;
                                }

                                $component->state(BankTransaction::entrySideForType($record->type));
                            })
                            ->afterStateUpdated(function ($state, Set $set): void {
                                $type = BankTransaction::defaultTypeForEntrySide($state);

                                $set('type', $type);

                                if (! BankTransaction::usesDepositSlip($type)) {
                                    $set('deposit_slip_number', null);
                                    $set('deposit_slip_date', null);
                                }
                            })
                            ->dehydrated(false),
                        Select::make('type')
                            ->options(fn (Get $get): array => BankTransaction::typeOptionsForEntrySide($get('entry_side')))
                            ->required()
                            ->searchable()
                            ->live()
                            ->default('daily_collection_deposit')
                            ->afterStateUpdated(function ($state, Set $set): void {
                                if (! BankTransaction::usesDepositSlip($state)) {
                                    $set('deposit_slip_number', null);
                                    $set('deposit_slip_date', null);
                                }
                            })
                            ->helperText('Use Cash collection deposit when money reaches bank. Use pending cash adjustment only for opening/reconciliation cash still outside bank.'),
                        TextInput::make('amount')
                            ->prefix('Rs')
                            ->required()
                            ->numeric()
                            ->inputMode('decimal')
                            ->minValue(0.01),
                        TextInput::make('deposit_slip_number')
                            ->label('Deposit slip number')
                            ->maxLength(100)
                            ->visible(fn (Get $get): bool => BankTransaction::usesDepositSlip($get('type'))),
                        DatePicker::make('deposit_slip_date')
                            ->label('Deposit slip date')
                            ->visible(fn (Get $get): bool => BankTransaction::usesDepositSlip($get('type'))),
                        TextInput::make('description')
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 2,
                            ]),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
