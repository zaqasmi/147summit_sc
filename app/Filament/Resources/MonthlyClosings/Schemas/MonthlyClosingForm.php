<?php

namespace App\Filament\Resources\MonthlyClosings\Schemas;

use App\Models\MonthlyClosing;
use App\Models\MonthlyCommission;
use App\Models\Staff;
use App\Models\StaffTransaction;
use App\Services\MonthlyClosingPreview;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                $set('commission_paid_overrides', self::paymentDefaults($state ?: today()));
                                $set('commission_payment_sources', self::paymentSourceDefaults($state ?: today()));
                            })
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
                Section::make('Commission staff payments')
                    ->description('Enter the total paid through this closing for each staff member. Advances and payouts already recorded are deducted separately. Overpayments carry forward as an advance; unpaid balances carry forward as dues.')
                    ->icon('heroicon-o-banknotes')
                    ->schema(fn (Get $get): array => Staff::query()->active()->commissioned()->orderBy('name')->get()
                        ->flatMap(fn (Staff $staff): array => [TextInput::make('commission_paid_overrides.'.$staff->id)
                            ->label($staff->name.' — paid at monthly closing')
                            ->prefix('Rs')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateHydrated(function (TextInput $component, mixed $state, Get $get) use ($staff): void {
                                if ($state === null) {
                                    $component->state(self::paymentDefaults($get('month') ?: today())[$staff->id] ?? 0);
                                }
                            })
                            ->helperText(function (Get $get, ?MonthlyClosing $record) use ($staff): string {
                                $report = self::previewReport($get, $record);
                                $row = collect($report['staff_shares'])->first(fn (array $row): bool => $row['staff']->is($staff));

                                return 'Previous balance '.self::money($row['previous_balance'])
                                    .' | Monthly commission '.self::money($row['monthly_share'])
                                    .' | Advance paid '.self::money($row['advance_paid'])
                                    .' | Other payouts '.self::money($row['payout_paid'])
                                    .' | Remaining due '.self::money(max(0, $row['remaining_balance']))
                                    .' | Advance carried forward '.self::money(max(0, -$row['remaining_balance']));
                            }),
                            Select::make('commission_payment_sources.'.$staff->id)
                                ->label($staff->name.' — paid from')
                                ->options(StaffTransaction::paidFromOptions())
                                ->required()
                                ->live()
                                ->afterStateHydrated(function (Select $component, mixed $state, Get $get) use ($staff): void {
                                    if ($state === null) {
                                        $component->state(self::paymentSourceDefaults($get('month') ?: today())[$staff->id] ?? 'cash');
                                    }
                                }),
                        ])
                        ->all()),
                Section::make('Printable Closing Report')
                    ->icon('heroicon-o-chart-bar')
                    ->extraAttributes(['class' => 'summit-monthly-closing-snapshot-section'])
                    ->schema([
                        View::make('filament.resources.monthly-closings.report-snapshot')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function previewReport(Get $get, ?MonthlyClosing $record = null): array
    {
        $month = self::fieldValue($get, $record, 'month') ?: today()->startOfMonth();
        $month = Carbon::parse($month)->startOfMonth();
        $defaults = MonthlyClosing::defaultsForMonth($month);
        $override = $defaults;

        foreach ($defaults as $key => $default) {
            $override[$key] = self::fieldValue($get, $record, $key) ?? $default;
        }

        $override['month'] = $month->toDateString();
        foreach (['rent_total', 'rent_paid_amount', 'construction_deduction_amount', 'construction_received_amount'] as $key) {
            $override[$key] = round((float) $override[$key], 2);
        }
        $override['construction_balance'] = round(max(0, $override['construction_deduction_amount'] - $override['construction_received_amount']), 2);
        $override['liabilities_verified'] = (bool) $override['liabilities_verified'];
        $payments = $get('commission_paid_overrides') ?? [];
        $override['commission_paid_overrides'] = self::paymentDefaults($month);
        foreach ($override['commission_paid_overrides'] as $staffId => $default) {
            $override['commission_paid_overrides'][$staffId] = (float) ($payments[$staffId] ?? $default);
        }

        return app(MonthlyClosingPreview::class)->report($month, $override);
    }

    public static function paymentDefaults(Carbon|string $month): array
    {
        $paid = MonthlyCommission::query()
            ->whereDate('month', Carbon::parse($month)->startOfMonth()->toDateString())
            ->pluck('paid_amount', 'staff_id')
            ->all();

        return Staff::query()->active()->commissioned()->get()
            ->mapWithKeys(fn (Staff $staff): array => [$staff->id => (float) ($paid[$staff->id] ?? 0)])
            ->all();
    }

    public static function paymentSourceDefaults(Carbon|string $month): array
    {
        $sources = MonthlyCommission::query()
            ->whereDate('month', Carbon::parse($month)->startOfMonth()->toDateString())
            ->pluck('paid_from', 'staff_id');

        return Staff::query()->active()->commissioned()->get()
            ->mapWithKeys(fn (Staff $staff): array => [$staff->id => $sources[$staff->id] ?? 'cash'])
            ->all();
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
        return 'Rs '.number_format((float) ($value ?? 0), 2);
    }
}
