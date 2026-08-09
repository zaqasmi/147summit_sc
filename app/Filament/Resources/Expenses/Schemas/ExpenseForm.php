<?php

namespace App\Filament\Resources\Expenses\Schemas;

use App\Models\Expense;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Expense Details')
                    ->icon('heroicon-o-receipt-refund')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        DatePicker::make('expense_date')
                            ->default(today())
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                if ($get('category') === Expense::CATEGORY_RENT) {
                                    $set('amount', Expense::scheduledRentForDate($state));
                                }
                            })
                            ->required(),
                        Select::make('staff_id')
                            ->relationship('staff', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('category')
                            ->options([
                                'General' => 'General',
                                'Utilities' => 'Utilities',
                                Expense::CATEGORY_RENT => 'Rent',
                                'Repairs' => 'Repairs',
                                'Supplies' => 'Supplies',
                                'Staff' => 'Staff',
                            ])
                            ->required()
                            ->default('General')
                            ->live()
                            ->afterStateUpdated(function ($state, Get $get, Set $set): void {
                                if ($state === Expense::CATEGORY_RENT && (float) ($get('amount') ?? 0) <= 0) {
                                    $set('amount', Expense::scheduledRentForDate($get('expense_date') ?? today()));
                                }
                            }),
                        TextInput::make('description')
                            ->required()
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 2,
                            ]),
                        TextInput::make('amount')
                            ->prefix('Rs')
                            ->required()
                            ->numeric()
                            ->inputMode('decimal'),
                        Select::make('paid_from')
                            ->label('Paid from')
                            ->options([
                                'cash' => 'Cash',
                                'petty_cash' => 'Petty cash',
                                'bank' => 'Bank',
                            ])
                            ->required()
                            ->default('cash'),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
