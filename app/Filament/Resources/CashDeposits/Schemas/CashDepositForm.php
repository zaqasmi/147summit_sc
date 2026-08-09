<?php

namespace App\Filament\Resources\CashDeposits\Schemas;

use App\Models\CustomerDue;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class CashDepositForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns([
                'default' => 1,
                'xl' => 12,
            ])
            ->components([
                Section::make('Table Sales')
                    ->icon('heroicon-o-table-cells')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 5,
                    ])
                    ->schema([
                        DatePicker::make('deposit_date')
                            ->label('Date')
                            ->default(today())
                            ->live()
                            ->afterStateUpdated(fn (Get $get, Set $set): null => self::updateClosingTotals($get, $set))
                            ->required(),
                        ...self::manualSaleFields(),
                        TextInput::make('total_sale')
                            ->label('Total sale')
                            ->prefix('Rs')
                            ->numeric()
                            ->inputMode('decimal')
                            ->readOnly()
                            ->default(0)
                            ->afterStateHydrated(function (TextInput $component, Get $get): void {
                                $component->state(self::previewTotals($get)['sales_total']);
                            })
                            ->dehydrated(false),
                    ]),

                Section::make('Expenses')
                    ->icon('heroicon-o-receipt-refund')
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 6,
                    ])
                    ->schema([
                        Repeater::make('expenses')
                            ->relationship()
                            ->label('Expense items')
                            ->defaultItems(0)
                            ->columns([
                                'default' => 1,
                                'md' => 4,
                            ])
                            ->collapsible()
                            ->addActionLabel('Add expense')
                            ->itemLabel(fn (array $state): ?string => filled($state['description'] ?? null)
                                ? $state['description']
                                : null)
                            ->afterStateUpdated(fn (Get $get, Set $set): null => self::updateClosingTotals($get, $set))
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data, Get $get): ?array => self::expenseDataForClosing($data, $get))
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data, Get $get): ?array => self::expenseDataForClosing($data, $get))
                            ->schema([
                                Select::make('category')
                                    ->options(self::expenseCategoryOptions())
                                    ->required()
                                    ->default('General')
                                    ->live()
                                    ->afterStateUpdated(fn (Get $get, Set $set): null => self::updateClosingTotals($get, $set, '../../')),
                                TextInput::make('description')
                                    ->required()
                                    ->columnSpan([
                                        'default' => 1,
                                        'md' => 2,
                                    ]),
                                TextInput::make('amount')
                                    ->prefix('Rs')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->inputMode('decimal')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set): null => self::updateClosingTotals($get, $set, '../../')),
                                Hidden::make('paid_from')
                                    ->default('cash'),
                            ]),
                        TextInput::make('manual_expense_total')
                            ->label('Expense total')
                            ->prefix('Rs')
                            ->numeric()
                            ->inputMode('decimal')
                            ->readOnly()
                            ->default(0)
                            ->dehydrateStateUsing(function ($state, Get $get): float {
                                $expenseRows = $get('expenses');

                                if (is_array($expenseRows) && self::hasExpenseRows($expenseRows)) {
                                    return round(self::expenseRowsTotal($expenseRows), 2);
                                }

                                return round((float) $state, 2);
                            }),
                    ]),

                Section::make('Customer Dues')
                    ->icon('heroicon-o-user-minus')
                    ->columnSpan([
                        'default' => 1,
                        'xl' => 6,
                    ])
                    ->schema([
                        Repeater::make('customer_dues')
                            ->label('Dues from customers')
                            ->defaultItems(0)
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                            ])
                            ->collapsible()
                            ->addActionLabel('Add customer due')
                            ->itemLabel(fn (array $state): ?string => filled($state['customer_name'] ?? null)
                                ? $state['customer_name']
                                : null)
                            ->afterStateHydrated(function (Repeater $component, Get $get): void {
                                if (filled($component->getState())) {
                                    return;
                                }

                                $duesAdded = (float) ($get('dues_added') ?? 0);

                                if ($duesAdded <= 0) {
                                    return;
                                }

                                $component->state([
                                    [
                                        'customer_name' => 'Existing customer due',
                                        'amount' => round($duesAdded, 2),
                                    ],
                                ]);
                            })
                            ->afterStateUpdated(fn (Get $get, Set $set): null => self::updateClosingTotals($get, $set))
                            ->dehydrateStateUsing(fn ($state): array => self::customerDueRowsForStorage(is_array($state) ? $state : []))
                            ->schema([
                                TextInput::make('customer_name')
                                    ->label('Customer name')
                                    ->required()
                                    ->live(onBlur: true),
                                TextInput::make('amount')
                                    ->label('Amount')
                                    ->prefix('Rs')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->inputMode('decimal')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set): null => self::updateClosingTotals($get, $set, '../../')),
                            ]),
                        TextInput::make('dues_added')
                            ->label('Customer dues total')
                            ->prefix('Rs')
                            ->numeric()
                            ->inputMode('decimal')
                            ->readOnly()
                            ->default(0)
                            ->afterStateHydrated(function (TextInput $component, Get $get): void {
                                $component->state(self::previewTotals($get)['dues_total']);
                            })
                            ->dehydrateStateUsing(function ($state, Get $get): float {
                                $customerDueRows = $get('customer_dues');

                                if (is_array($customerDueRows)) {
                                    return round(self::customerDueRowsTotal($customerDueRows), 2);
                                }

                                return round((float) $state, 2);
                            }),
                        Repeater::make('customerDuePayments')
                            ->relationship('customerDuePayments')
                            ->label('Dues recovered')
                            ->defaultItems(0)
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                            ])
                            ->collapsible()
                            ->addActionLabel('Add recovered due')
                            ->itemLabel(function (array $state): ?string {
                                $customerDueId = $state['customer_due_id'] ?? null;

                                if (! $customerDueId) {
                                    return null;
                                }

                                return CustomerDue::query()->find($customerDueId)?->customer_name;
                            })
                            ->afterStateUpdated(fn (Get $get, Set $set): null => self::updateClosingTotals($get, $set))
                            ->mutateRelationshipDataBeforeCreateUsing(fn (array $data, Get $get): ?array => self::customerDuePaymentDataForClosing($data, $get))
                            ->mutateRelationshipDataBeforeSaveUsing(fn (array $data, Get $get): ?array => self::customerDuePaymentDataForClosing($data, $get))
                            ->schema([
                                Select::make('customer_due_id')
                                    ->label('Customer')
                                    ->options(fn (Get $get): array => self::customerDueOptions($get))
                                    ->searchable()
                                    ->required()
                                    ->live(),
                                TextInput::make('amount')
                                    ->label('Amount paid')
                                    ->prefix('Rs')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->inputMode('decimal')
                                    ->default(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set): null => self::updateClosingTotals($get, $set, '../../')),
                            ]),
                        TextInput::make('dues_recovered')
                            ->label('Customer dues recovered')
                            ->prefix('Rs')
                            ->numeric()
                            ->inputMode('decimal')
                            ->readOnly()
                            ->default(0)
                            ->afterStateHydrated(function (TextInput $component, Get $get): void {
                                $component->state(self::previewTotals($get)['dues_recovered_total']);
                            })
                            ->dehydrateStateUsing(function ($state, Get $get): float {
                                $customerDuePaymentRows = $get('customerDuePayments');

                                if (is_array($customerDuePaymentRows)) {
                                    return round(self::customerDuePaymentRowsTotal($customerDuePaymentRows), 2);
                                }

                                return round((float) $state, 2);
                            }),
                    ]),

                Section::make('Collection')
                    ->icon('heroicon-o-banknotes')
                    ->columnSpanFull()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        TextInput::make('petty_cash_kept')
                            ->label('Petty cash in counter')
                            ->prefix('Rs')
                            ->numeric()
                            ->inputMode('decimal')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set): null => self::updateClosingTotals($get, $set))
                            ->dehydrateStateUsing(fn ($state): float => self::moneyValue($state)),
                        TextInput::make('cash_to_be_collected')
                            ->label('Cash to be collected')
                            ->prefix('Rs')
                            ->numeric()
                            ->inputMode('decimal')
                            ->readOnly()
                            ->default(0)
                            ->afterStateHydrated(function (TextInput $component, Get $get): void {
                                $component->state(self::previewTotals($get)['cash_to_be_collected']);
                            })
                            ->dehydrated(false),
                        TextInput::make('amount_collected_from_staff')
                            ->label('Actual daily collection')
                            ->prefix('Rs')
                            ->required()
                            ->numeric()
                            ->inputMode('decimal')
                            ->default(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set): null => self::updateClosingTotals($get, $set))
                            ->dehydrateStateUsing(fn ($state): float => self::moneyValue($state)),
                        Textarea::make('notes')
                            ->columnSpanFull(),
                        Hidden::make('closing_source')
                            ->default('manual')
                            ->dehydrateStateUsing(fn (): string => 'manual'),
                        Hidden::make('cash_collected_from_counter')
                            ->default(0)
                            ->dehydrateStateUsing(fn ($state, Get $get): float => self::previewTotals($get)['cash_after_expense_due']),
                        Hidden::make('opening_petty_cash')
                            ->default(0)
                            ->dehydrateStateUsing(fn (): float => 0.0),
                        Hidden::make('bank_deposit_amount')
                            ->default(0)
                            ->dehydrateStateUsing(fn (): float => 0.0),
                    ]),

                Section::make('Summary')
                    ->icon('heroicon-o-calculator')
                    ->columnSpanFull()
                    ->schema([
                        Html::make(fn (Get $get): HtmlString => self::closingSummary($get)),
                    ]),
            ]);
    }

    /**
     * @return array<int, TextInput>
     */
    private static function manualSaleFields(): array
    {
        return collect([1, 2, 3, 4])
            ->map(fn (int $number): TextInput => TextInput::make('manual_table_'.$number.'_sale')
                ->label('Table '.$number.' sale')
                ->prefix('Rs')
                ->numeric()
                ->inputMode('decimal')
                ->default(0)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Get $get, Set $set): null => self::updateClosingTotals($get, $set))
                ->dehydrateStateUsing(fn ($state): float => self::moneyValue($state)))
            ->all();
    }

    private static function moneyValue(mixed $state): float
    {
        return round((float) ($state ?? 0), 2);
    }

    /**
     * @return array<string, string>
     */
    private static function expenseCategoryOptions(): array
    {
        return [
            'General' => 'General',
            'Utilities' => 'Utilities',
            'Repairs' => 'Repairs',
            'Supplies' => 'Supplies',
            'Staff' => 'Staff',
        ];
    }

    private static function updateClosingTotals(Get $get, Set $set, string $prefix = ''): null
    {
        $totals = self::previewTotals($get, $prefix);

        $set($prefix.'total_sale', $totals['sales_total']);
        $set($prefix.'manual_expense_total', $totals['expense_total']);
        $set($prefix.'cash_collected_from_counter', $totals['cash_after_expense_due']);
        $set($prefix.'cash_to_be_collected', $totals['cash_to_be_collected']);
        $set($prefix.'bank_deposit_amount', 0);
        $set($prefix.'opening_petty_cash', 0);
        $set($prefix.'dues_added', $totals['dues_total']);
        $set($prefix.'dues_recovered', $totals['dues_recovered_total']);
        $set($prefix.'closing_source', 'manual');

        return null;
    }

    /**
     * @return array<string, float>
     */
    private static function previewTotals(Get $get, string $prefix = ''): array
    {
        $salesTotal = collect([1, 2, 3, 4])
            ->sum(fn (int $number): float => (float) ($get($prefix.'manual_table_'.$number.'_sale') ?? 0));
        $expenseRows = $get($prefix.'expenses');
        $expenseRows = is_array($expenseRows) ? $expenseRows : [];
        $expenseTotal = self::hasExpenseRows($expenseRows)
            ? self::expenseRowsTotal($expenseRows)
            : (float) ($get($prefix.'manual_expense_total') ?? 0);
        $customerDueRows = $get($prefix.'customer_dues');
        $duesTotal = is_array($customerDueRows)
            ? self::customerDueRowsTotal($customerDueRows)
            : (float) ($get($prefix.'dues_added') ?? 0);
        $customerDuePaymentRows = $get($prefix.'customerDuePayments');
        $duesRecoveredTotal = is_array($customerDuePaymentRows)
            ? self::customerDuePaymentRowsTotal($customerDuePaymentRows)
            : (float) ($get($prefix.'dues_recovered') ?? 0);
        $pettyCash = (float) ($get($prefix.'petty_cash_kept') ?? 0);
        $actualCollected = (float) ($get($prefix.'amount_collected_from_staff') ?? 0);
        $cashAfterExpenseDue = max(0, $salesTotal - $duesTotal + $duesRecoveredTotal - $expenseTotal);
        $cashToBeCollected = max(0, $cashAfterExpenseDue - $pettyCash);

        return [
            'sales_total' => round($salesTotal, 2),
            'expense_total' => round($expenseTotal, 2),
            'petty_cash' => round($pettyCash, 2),
            'dues_total' => round($duesTotal, 2),
            'dues_recovered_total' => round($duesRecoveredTotal, 2),
            'cash_after_expense_due' => round($cashAfterExpenseDue, 2),
            'cash_to_be_collected' => round($cashToBeCollected, 2),
            'actual_collected' => round($actualCollected, 2),
        ];
    }

    private static function expenseDataForClosing(array $data, Get $get): ?array
    {
        $amount = (float) ($data['amount'] ?? 0);
        $description = trim((string) ($data['description'] ?? ''));

        if ($amount <= 0 || $description === '') {
            return null;
        }

        $date = $get('deposit_date') ?: $get('../../deposit_date') ?: today()->toDateString();
        $staffId = $get('staff_id') ?: $get('../../staff_id');
        $category = (string) ($data['category'] ?? 'General');

        return [
            'expense_date' => Carbon::parse($date)->toDateString(),
            'staff_id' => filled($staffId) ? $staffId : null,
            'category' => array_key_exists($category, self::expenseCategoryOptions()) ? $category : 'General',
            'description' => $description,
            'amount' => round($amount, 2),
            'paid_from' => 'cash',
            'notes' => null,
        ];
    }

    private static function customerDuePaymentDataForClosing(array $data, Get $get): ?array
    {
        $customerDueId = $data['customer_due_id'] ?? null;
        $amount = (float) ($data['amount'] ?? 0);

        if (! $customerDueId || $amount <= 0) {
            return null;
        }

        $date = $get('deposit_date') ?: $get('../../deposit_date') ?: today()->toDateString();

        return [
            'customer_due_id' => $customerDueId,
            'payment_date' => Carbon::parse($date)->toDateString(),
            'amount' => round($amount, 2),
            'notes' => null,
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function customerDueOptions(Get $get): array
    {
        $selectedCustomerDueId = $get('customer_due_id');

        return CustomerDue::query()
            ->where(function ($query) use ($selectedCustomerDueId): void {
                $query->withBalance();

                if ($selectedCustomerDueId) {
                    $query->orWhere('id', $selectedCustomerDueId);
                }
            })
            ->orderBy('customer_name')
            ->get()
            ->mapWithKeys(fn (CustomerDue $due): array => [
                $due->id => $due->customer_name.' (Rs '.number_format((float) $due->balance_due, 2).')',
            ])
            ->all();
    }

    /**
     * @param  array<string, array<string, mixed>>  $expenseRows
     */
    private static function expenseRowsTotal(array $expenseRows): float
    {
        return collect($expenseRows)
            ->sum(fn (array $row): float => (float) ($row['amount'] ?? 0));
    }

    /**
     * @param  array<string, array<string, mixed>>  $expenseRows
     */
    private static function hasExpenseRows(array $expenseRows): bool
    {
        return collect($expenseRows)
            ->contains(fn (array $row): bool => (float) ($row['amount'] ?? 0) > 0 || filled($row['description'] ?? null));
    }

    /**
     * @param  array<string, array<string, mixed>>  $customerDueRows
     */
    private static function customerDueRowsTotal(array $customerDueRows): float
    {
        return collect($customerDueRows)
            ->sum(fn (array $row): float => (float) ($row['amount'] ?? 0));
    }

    /**
     * @param  array<string, array<string, mixed>>  $customerDuePaymentRows
     */
    private static function customerDuePaymentRowsTotal(array $customerDuePaymentRows): float
    {
        return collect($customerDuePaymentRows)
            ->sum(fn (array $row): float => (float) ($row['amount'] ?? 0));
    }

    /**
     * @param  array<string, array<string, mixed>>  $customerDueRows
     * @return array<int, array{customer_name: string, amount: float}>
     */
    private static function customerDueRowsForStorage(array $customerDueRows): array
    {
        return collect($customerDueRows)
            ->map(fn (array $row): array => [
                'customer_name' => trim((string) ($row['customer_name'] ?? '')),
                'amount' => round((float) ($row['amount'] ?? 0), 2),
            ])
            ->filter(fn (array $row): bool => $row['customer_name'] !== '' && $row['amount'] > 0)
            ->values()
            ->all();
    }

    private static function closingSummary(Get $get): HtmlString
    {
        $totals = self::previewTotals($get);

        return new HtmlString(sprintf(
            <<<'HTML'
<div class="summit-closing-summary">
    <div class="summit-stat-grid">
        <div class="summit-stat-card" data-tone="teal">
            <div class="summit-stat-label">Total sale</div>
            <div class="summit-stat-value">%s</div>
        </div>
        <div class="summit-stat-card" data-tone="rose">
            <div class="summit-stat-label">Expense</div>
            <div class="summit-stat-value">%s</div>
        </div>
        <div class="summit-stat-card" data-tone="rose">
            <div class="summit-stat-label">Customer dues</div>
            <div class="summit-stat-value">%s</div>
        </div>
        <div class="summit-stat-card" data-tone="green">
            <div class="summit-stat-label">Dues recovered</div>
            <div class="summit-stat-value">%s</div>
        </div>
        <div class="summit-stat-card" data-tone="teal">
            <div class="summit-stat-label">Petty cash in counter</div>
            <div class="summit-stat-value">%s</div>
        </div>
        <div class="summit-stat-card" data-tone="green">
            <div class="summit-stat-label">Cash to be collected</div>
            <div class="summit-stat-value">%s</div>
        </div>
        <div class="summit-stat-card" data-tone="green">
            <div class="summit-stat-label">Actual daily collection</div>
            <div class="summit-stat-value">%s</div>
        </div>
    </div>

    <dl class="summit-closing-formula">
        <div><dt>Total sale</dt><dd>%s</dd></div>
        <div><dt>Minus customer dues</dt><dd>%s</dd></div>
        <div><dt>Plus dues recovered</dt><dd>%s</dd></div>
        <div><dt>Minus expenses</dt><dd>%s</dd></div>
        <div><dt>Minus petty cash in counter</dt><dd>%s</dd></div>
        <div><dt>Cash to be collected</dt><dd>%s</dd></div>
        <div><dt>Actual daily collection</dt><dd>%s</dd></div>
        <div><dt>Difference</dt><dd>%s</dd></div>
    </dl>
</div>
HTML,
            self::money($totals['sales_total']),
            self::money($totals['expense_total']),
            self::money($totals['dues_total']),
            self::money($totals['dues_recovered_total']),
            self::money($totals['petty_cash']),
            self::money($totals['cash_to_be_collected']),
            self::money($totals['actual_collected']),
            self::money($totals['sales_total']),
            self::money($totals['dues_total']),
            self::money($totals['dues_recovered_total']),
            self::money($totals['expense_total']),
            self::money($totals['petty_cash']),
            self::money($totals['cash_to_be_collected']),
            self::money($totals['actual_collected']),
            self::money($totals['actual_collected'] - $totals['cash_to_be_collected']),
        ));
    }

    private static function money(float|int|string|null $amount): string
    {
        return 'Rs '.number_format((float) $amount, 2);
    }
}
