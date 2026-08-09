<?php

namespace App\Filament\Resources\MonthlyCommissions\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class MonthlyCommissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('staff_id')
                    ->relationship('staff', 'name')
                    ->required(),
                DatePicker::make('month')
                    ->required(),
                TextInput::make('cash_collected')
                    ->prefix('Rs')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('expense_total')
                    ->prefix('Rs')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('net_profit')
                    ->prefix('Rs')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('commission_rate')
                    ->suffix('%')
                    ->required()
                    ->numeric()
                    ->default(25),
                TextInput::make('commission_amount')
                    ->prefix('Rs')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('carried_forward_from_previous')
                    ->prefix('Rs')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('advances_deducted')
                    ->prefix('Rs')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('paid_amount')
                    ->prefix('Rs')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('balance_due')
                    ->prefix('Rs')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('generated_at'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
