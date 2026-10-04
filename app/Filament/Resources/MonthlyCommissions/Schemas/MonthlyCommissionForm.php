<?php

namespace App\Filament\Resources\MonthlyCommissions\Schemas;

use App\Models\StaffTransaction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MonthlyCommissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('staff_id')
                    ->disabled()
                    ->relationship('staff', 'name')
                    ->required(),
                DatePicker::make('month')
                    ->disabled()
                    ->required(),
                TextInput::make('cash_collected')
                    ->disabled()
                    ->prefix('Rs')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('expense_total')
                    ->disabled()
                    ->prefix('Rs')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('net_profit')
                    ->disabled()
                    ->prefix('Rs')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('commission_rate')
                    ->disabled()
                    ->suffix('%')
                    ->required()
                    ->numeric()
                    ->default(25),
                TextInput::make('commission_amount')
                    ->disabled()
                    ->prefix('Rs')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('carried_forward_from_previous')
                    ->disabled()
                    ->prefix('Rs')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('advances_deducted')
                    ->disabled()
                    ->prefix('Rs')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('paid_amount')
                    ->label('Paid at monthly closing')
                    ->helperText('Total paid through the closing. Recorded advances and payouts are deducted separately; remaining dues or advance credit are recalculated on save.')
                    ->minValue(0)
                    ->prefix('Rs')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('paid_from')
                    ->label('Closing payment from')
                    ->options(StaffTransaction::paidFromOptions())
                    ->required()
                    ->default('cash'),
                DatePicker::make('paid_on')
                    ->label('Closing payment date')
                    ->helperText('A new payment with no date uses today.'),
                TextInput::make('balance_due')
                    ->disabled()
                    ->prefix('Rs')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('generated_at')
                    ->disabled(),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
