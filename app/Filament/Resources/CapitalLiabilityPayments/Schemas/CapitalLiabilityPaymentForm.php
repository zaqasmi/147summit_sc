<?php

namespace App\Filament\Resources\CapitalLiabilityPayments\Schemas;

use App\Models\CapitalLiability;
use App\Models\CapitalLiabilityPayment;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CapitalLiabilityPaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Capital Installment')
                    ->icon('heroicon-o-wallet')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        Select::make('capital_liability_id')
                            ->label('Loan / capital item')
                            ->relationship(
                                'capitalLiability',
                                'title',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->orderBy('title'),
                            )
                            ->getOptionLabelFromRecordUsing(fn (CapitalLiability $record): string => trim($record->title.' - '.($record->lender_name ?: $record->source_label)))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 2,
                            ]),
                        DatePicker::make('payment_date')
                            ->default(today())
                            ->required(),
                        TextInput::make('amount')
                            ->prefix('Rs')
                            ->required()
                            ->numeric()
                            ->inputMode('decimal'),
                        Select::make('paid_from')
                            ->label('Paid from')
                            ->options(CapitalLiabilityPayment::paidFromOptions())
                            ->required()
                            ->default('cash')
                            ->helperText('Cash reduces pending bank cash. Bank creates a bank debit. Owner / other source creates an Owner Capital entry for recovery tracking.'),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
