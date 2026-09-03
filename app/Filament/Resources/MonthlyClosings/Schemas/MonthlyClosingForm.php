<?php

namespace App\Filament\Resources\MonthlyClosings\Schemas;

use App\Models\MonthlyClosing;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

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
                            ->live()
                            ->required(),
                        Select::make('status')
                            ->options(MonthlyClosing::statusOptions())
                            ->default(MonthlyClosing::STATUS_DRAFT)
                            ->live()
                            ->required(),
                        TextInput::make('rent_total')
                            ->label('Total rent')
                            ->prefix('Rs')
                            ->numeric()
                            ->placeholder('0.00')
                            ->live(onBlur: true)
                            ->required()
                            ->default(0),
                        TextInput::make('rent_paid_amount')
                            ->label('Rent paid')
                            ->prefix('Rs')
                            ->numeric()
                            ->placeholder('0.00')
                            ->live(onBlur: true)
                            ->required()
                            ->default(0),
                        Select::make('rent_paid_from')
                            ->label('Rent paid from')
                            ->options(MonthlyClosing::paidFromOptions())
                            ->default('bank')
                            ->live()
                            ->required(),
                        TextInput::make('construction_deduction_amount')
                            ->label('Construction deduction')
                            ->prefix('Rs')
                            ->numeric()
                            ->placeholder('0.00')
                            ->live(onBlur: true)
                            ->required()
                            ->default(0),
                        TextInput::make('construction_received_amount')
                            ->label('Saved in other account')
                            ->prefix('Rs')
                            ->numeric()
                            ->placeholder('0.00')
                            ->live(onBlur: true)
                            ->required()
                            ->default(0),
                        TextInput::make('construction_account_name')
                            ->label('Other account name')
                            ->placeholder('Other bank / savings account')
                            ->live(onBlur: true)
                            ->maxLength(255),
                        Toggle::make('liabilities_verified')
                            ->label('Liabilities paid and verified')
                            ->live(),
                        Textarea::make('notes')
                            ->placeholder('Optional closing notes')
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                    ]),
                Section::make('Report Snapshot')
                    ->icon('heroicon-o-chart-bar')
                    ->extraAttributes(['class' => 'summit-monthly-closing-snapshot-section'])
                    ->hiddenOn('create')
                    ->schema([
                        View::make('filament.resources.monthly-closings.report-snapshot')
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                                'xl' => 4,
                            ])
                            ->schema([
                                self::monthPlaceholder(),
                                self::statusPlaceholder(),
                                self::moneyPlaceholder('sales_total_snapshot', 'Sales total', 'sales_total'),
                                self::moneyPlaceholder('cash_collected_snapshot', 'Cash collected', 'cash_collected'),
                                self::moneyPlaceholder('expense_total_snapshot', 'Expenses', 'expense_total'),
                                self::moneyPlaceholder('net_profit_snapshot', 'Net profit', 'net_profit'),
                                self::moneyPlaceholder('commission_amount_snapshot', 'Commission', 'commission_amount'),
                                self::moneyPlaceholder('staff_paid_total_snapshot', 'Staff paid', 'staff_paid_total'),
                                self::moneyPlaceholder('liabilities_paid_amount_snapshot', 'Liabilities paid', 'liabilities_paid_amount'),
                                self::moneyPlaceholder('rent_total_snapshot', 'Rent total', 'rent_total'),
                                self::moneyPlaceholder('rent_paid_amount_snapshot', 'Rent paid', 'rent_paid_amount'),
                                self::rentSourcePlaceholder(),
                                self::moneyPlaceholder('construction_deduction_snapshot', 'Construction deduction', 'construction_deduction_amount'),
                                self::moneyPlaceholder('construction_received_snapshot', 'Saved other account', 'construction_received_amount'),
                                self::moneyPlaceholder('construction_balance_snapshot', 'Construction balance', 'construction_balance'),
                                self::liabilitiesVerifiedPlaceholder(),
                                self::textPlaceholder('construction_account_snapshot', 'Other account name', 'construction_account_name'),
                                self::textPlaceholder('closed_by_snapshot', 'Closed by', 'closedBy.name'),
                                self::closedAtPlaceholder(),
                                self::textPlaceholder('notes_snapshot', 'Notes', 'notes')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function monthPlaceholder(): Placeholder
    {
        return Placeholder::make('month_snapshot')
            ->label('Month')
            ->content(function (Get $get, ?MonthlyClosing $record): string {
                $month = self::fieldValue($get, $record, 'month');

                return blank($month) ? '-' : Carbon::parse($month)->format('F Y');
            });
    }

    private static function statusPlaceholder(): Placeholder
    {
        return Placeholder::make('status_snapshot')
            ->label('Status')
            ->badge()
            ->color(fn (Get $get, ?MonthlyClosing $record): string => self::fieldValue($get, $record, 'status') === MonthlyClosing::STATUS_CLOSED ? 'success' : 'warning')
            ->content(function (Get $get, ?MonthlyClosing $record): string {
                $status = self::fieldValue($get, $record, 'status') ?: MonthlyClosing::STATUS_DRAFT;

                return MonthlyClosing::statusOptions()[$status] ?? str($status)->headline()->toString();
            });
    }

    private static function rentSourcePlaceholder(): Placeholder
    {
        return Placeholder::make('rent_paid_from_snapshot')
            ->label('Rent paid from')
            ->badge()
            ->color(fn (Get $get, ?MonthlyClosing $record): string => self::fieldValue($get, $record, 'rent_paid_from') === 'cash' ? 'warning' : 'info')
            ->content(function (Get $get, ?MonthlyClosing $record): string {
                $source = self::fieldValue($get, $record, 'rent_paid_from') ?: 'bank';

                return MonthlyClosing::paidFromOptions()[$source] ?? str($source)->headline()->toString();
            });
    }

    private static function liabilitiesVerifiedPlaceholder(): Placeholder
    {
        return Placeholder::make('liabilities_verified_snapshot')
            ->label('Liabilities verified')
            ->badge()
            ->color(fn (Get $get, ?MonthlyClosing $record): string => self::fieldValue($get, $record, 'liabilities_verified') ? 'success' : 'warning')
            ->content(fn (Get $get, ?MonthlyClosing $record): string => self::fieldValue($get, $record, 'liabilities_verified') ? 'Verified' : 'Pending');
    }

    private static function closedAtPlaceholder(): Placeholder
    {
        return Placeholder::make('closed_at_snapshot')
            ->label('Closed at')
            ->content(function (?MonthlyClosing $record): string {
                return $record?->closed_at ? Carbon::parse($record->closed_at)->format('d M Y, h:i A') : '-';
            });
    }

    private static function moneyPlaceholder(string $name, string $label, string $statePath): Placeholder
    {
        return Placeholder::make($name)
            ->label($label)
            ->content(fn (Get $get, ?MonthlyClosing $record): string => self::money(self::fieldValue($get, $record, $statePath)));
    }

    private static function textPlaceholder(string $name, string $label, string $statePath): Placeholder
    {
        return Placeholder::make($name)
            ->label($label)
            ->content(fn (Get $get, ?MonthlyClosing $record): string => filled($value = self::fieldValue($get, $record, $statePath)) ? (string) $value : '-');
    }

    private static function fieldValue(Get $get, ?MonthlyClosing $record, string $statePath): mixed
    {
        $value = $get($statePath);

        if (($value !== null) && ($value !== '')) {
            return $value;
        }

        if (! $record) {
            return null;
        }

        if (str_contains($statePath, '.')) {
            return data_get($record, $statePath);
        }

        return $record->getAttribute($statePath);
    }

    private static function money(mixed $value): string
    {
        return 'Rs ' . number_format((float) ($value ?? 0), 2);
    }
}
