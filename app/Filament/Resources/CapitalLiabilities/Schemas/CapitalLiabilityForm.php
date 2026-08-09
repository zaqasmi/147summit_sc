<?php

namespace App\Filament\Resources\CapitalLiabilities\Schemas;

use App\Models\CapitalLiability;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CapitalLiabilityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Capital Liability')
                    ->icon('heroicon-o-scale')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        DatePicker::make('start_date')
                            ->default(today())
                            ->required(),
                        TextInput::make('title')
                            ->label('Loan / item name')
                            ->placeholder('Bank loan, Solar from friend, ACs from friend')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 2,
                            ]),
                        Select::make('source_type')
                            ->label('Source')
                            ->options(CapitalLiability::sourceOptions())
                            ->default('friend')
                            ->required(),
                        TextInput::make('lender_name')
                            ->label('Bank / friend name')
                            ->maxLength(255),
                        Select::make('category')
                            ->options(CapitalLiability::categoryOptions())
                            ->default('Loan')
                            ->required(),
                        TextInput::make('principal_amount')
                            ->label('Total amount')
                            ->prefix('Rs')
                            ->required()
                            ->numeric()
                            ->inputMode('decimal'),
                        TextInput::make('installment_amount')
                            ->label('Installment amount')
                            ->prefix('Rs')
                            ->numeric()
                            ->inputMode('decimal')
                            ->default(0),
                        Select::make('installment_frequency')
                            ->label('Installment frequency')
                            ->options(CapitalLiability::frequencyOptions())
                            ->default('monthly')
                            ->required(),
                        DatePicker::make('due_date')
                            ->label('Final due date'),
                        Select::make('status')
                            ->options(CapitalLiability::statusOptions())
                            ->default('active')
                            ->required(),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
