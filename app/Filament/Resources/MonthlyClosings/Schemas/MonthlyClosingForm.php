<?php

namespace App\Filament\Resources\MonthlyClosings\Schemas;

use App\Models\MonthlyClosing;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MonthlyClosingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Month End Closing')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        DatePicker::make('month')
                            ->label('Month')
                            ->default(today()->startOfMonth())
                            ->required(),
                        Select::make('status')
                            ->options(MonthlyClosing::statusOptions())
                            ->default(MonthlyClosing::STATUS_DRAFT)
                            ->required(),
                        TextInput::make('rent_total')
                            ->label('Total rent')
                            ->prefix('Rs')
                            ->numeric()
                            ->required()
                            ->default(0),
                        TextInput::make('rent_paid_amount')
                            ->label('Rent paid')
                            ->prefix('Rs')
                            ->numeric()
                            ->required()
                            ->default(0),
                        Select::make('rent_paid_from')
                            ->label('Rent paid from')
                            ->options(MonthlyClosing::paidFromOptions())
                            ->default('bank')
                            ->required(),
                        TextInput::make('construction_deduction_amount')
                            ->label('Construction deduction')
                            ->prefix('Rs')
                            ->numeric()
                            ->required()
                            ->default(0),
                        TextInput::make('construction_received_amount')
                            ->label('Saved in other account')
                            ->prefix('Rs')
                            ->numeric()
                            ->required()
                            ->default(0),
                        TextInput::make('construction_account_name')
                            ->label('Other account name')
                            ->maxLength(255),
                        Toggle::make('liabilities_verified')
                            ->label('Liabilities paid and verified'),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
                Section::make('Report Snapshot')
                    ->icon('heroicon-o-chart-bar')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 4,
                    ])
                    ->schema([
                        TextInput::make('sales_total')
                            ->prefix('Rs')
                            ->numeric()
                            ->default(0),
                        TextInput::make('cash_collected')
                            ->prefix('Rs')
                            ->numeric()
                            ->default(0),
                        TextInput::make('expense_total')
                            ->prefix('Rs')
                            ->numeric()
                            ->default(0),
                        TextInput::make('net_profit')
                            ->prefix('Rs')
                            ->numeric()
                            ->default(0),
                        TextInput::make('commission_amount')
                            ->prefix('Rs')
                            ->numeric()
                            ->default(0),
                        TextInput::make('staff_paid_total')
                            ->prefix('Rs')
                            ->numeric()
                            ->default(0),
                        TextInput::make('liabilities_paid_amount')
                            ->prefix('Rs')
                            ->numeric()
                            ->default(0),
                    ]),
            ]);
    }
}
