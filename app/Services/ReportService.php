<?php

namespace App\Services;

use App\Models\BankTransaction;
use App\Models\CapitalLiability;
use App\Models\CapitalLiabilityPayment;
use App\Models\CashDeposit;
use App\Models\CommissionRate;
use App\Models\CustomerDue;
use App\Models\CustomerDueCharge;
use App\Models\CustomerDuePayment;
use App\Models\Expense;
use App\Models\GameAddOn;
use App\Models\GameParticipant;
use App\Models\GameSession;
use App\Models\MonthlyClosing;
use App\Models\MonthlyCommission;
use App\Models\OwnerCapital;
use App\Models\Payment;
use App\Models\SnookerTable;
use App\Models\Staff;
use App\Models\StaffTransaction;
use Illuminate\Support\Carbon;

class ReportService
{
    /**
     * @return array<string, mixed>
     */
    public function daily(Carbon|string $date, bool $withCapital = true): array
    {
        $day = Carbon::parse($date)->toDateString();

        $deposits = CashDeposit::query()
            ->with('staff')
            ->whereDate('deposit_date', $day)
            ->latest('id')
            ->get();

        $manualDeposits = $deposits->where('closing_source', 'manual');
        $usesManualClosing = $manualDeposits->isNotEmpty();
        $gameSessionClosingDeposits = $deposits->whereIn('closing_source', ['game_sessions', 'system']);
        $usesGameSessionClosing = $gameSessionClosingDeposits->isNotEmpty();
        $usesExplicitClosing = $usesManualClosing || $usesGameSessionClosing;

        $checkedOutSessionIds = GameSession::query()
            ->whereDate('checked_out_at', $day)
            ->pluck('id');

        $tableSales = SnookerTable::query()
            ->orderBy('number')
            ->get()
            ->map(function (SnookerTable $table) use ($day, $manualDeposits, $usesManualClosing): array {
                $sessionIds = $table->gameSessions()
                    ->whereDate('checked_out_at', $day)
                    ->pluck('id');

                $detailedSales = (float) GameParticipant::query()
                    ->whereIn('game_session_id', $sessionIds)
                    ->sum('total_due');

                return [
                    'table' => $table,
                    'sessions' => $sessionIds->count(),
                    'frames' => (int) GameSession::query()
                        ->whereIn('id', $sessionIds)
                        ->whereIn('game_type', ['one_to_one', 'doubles'])
                        ->sum('frames_played'),
                    'sales' => $usesManualClosing
                        ? (float) $manualDeposits->sum('manual_table_'.$table->number.'_sale')
                        : $detailedSales,
                    'due' => max(0, (float) GameParticipant::query()
                        ->whereIn('game_session_id', $sessionIds)
                        ->sum('total_due') - (float) GameParticipant::query()
                        ->whereIn('game_session_id', $sessionIds)
                        ->sum('amount_paid')),
                ];
            });

        $grossSalesTotal = $usesManualClosing
            ? $this->manualSalesTotal($manualDeposits)
            : (float) GameParticipant::query()
                ->whereIn('game_session_id', $checkedOutSessionIds)
                ->sum('total_due');

        $paidAgainstSelectedSessions = (float) GameParticipant::query()
            ->whereIn('game_session_id', $checkedOutSessionIds)
            ->sum('amount_paid');

        $dueTotal = max(0, $grossSalesTotal - $paidAgainstSelectedSessions);
        $storedDuesAdded = (float) $deposits->sum('dues_added');
        $manualCustomerDuesAdded = $usesManualClosing
            ? $this->manualCustomerDuesTotal($manualDeposits)
            : 0.0;
        $duesAdded = match (true) {
            $usesManualClosing => max($storedDuesAdded, $manualCustomerDuesAdded),
            $usesGameSessionClosing => max($storedDuesAdded, $dueTotal),
            $storedDuesAdded > 0 => $storedDuesAdded,
            default => $dueTotal,
        };
        $duesRecovered = (float) $deposits->sum('dues_recovered');
        $duesDiscounted = (float) CustomerDuePayment::query()
            ->whereDate('payment_date', $day)
            ->sum('discount_amount');
        $salesTotal = max(0, $grossSalesTotal - $duesAdded + $duesRecovered);

        $cashCollected = (float) Payment::query()
            ->whereDate('payment_date', $day)
            ->sum('amount');

        $dayExpenseRows = Expense::query()
            ->whereDate('expense_date', $day)
            ->get(['expense_date', 'category', 'description', 'amount', 'cash_deposit_id']);
        $expenseRowsTotal = (float) $dayExpenseRows->sum('amount');
        $rentExpenseTotal = (float) $dayExpenseRows
            ->filter(fn (Expense $expense): bool => $this->isRentExpense($expense))
            ->sum('amount');
        $expenseTotal = $usesManualClosing
            ? (float) $manualDeposits->sum('manual_expense_total')
            : max(0, $expenseRowsTotal - $rentExpenseTotal);
        $dailyExpenseTotal = $expenseTotal;

        $amountCollectedFromStaff = (float) $deposits->sum('amount_collected_from_staff');
        $closingCashAfterExpenseAndDue = $usesExplicitClosing
            ? max(0, $salesTotal - $expenseTotal)
            : 0.0;

        if ($usesManualClosing || $deposits->isNotEmpty()) {
            $cashCollected = $usesExplicitClosing
                ? max(0, $amountCollectedFromStaff)
                : max(0, $salesTotal);
        } elseif ($duesRecovered > 0) {
            $cashCollected += $duesRecovered;
        }

        $addOnTotal = $usesManualClosing
            ? 0.0
            : (float) GameAddOn::query()
                ->whereIn('game_session_id', $checkedOutSessionIds)
                ->sum('total_amount');

        $staffPaidTotal = (float) StaffTransaction::query()
            ->whereIn('type', ['advance', 'payout'])
            ->whereDate('transaction_date', $day)
            ->sum('amount');
        $capitalInstallmentsPaid = (float) CapitalLiabilityPayment::query()
            ->whereDate('payment_date', $day)
            ->sum('amount');
        $capitalInstallmentsPaidFromBusiness = (float) CapitalLiabilityPayment::query()
            ->whereDate('payment_date', $day)
            ->whereIn('paid_from', ['cash', 'petty_cash', 'bank'])
            ->sum('amount');
        $capitalInstallmentsPaidFromCounter = (float) CapitalLiabilityPayment::query()
            ->whereDate('payment_date', $day)
            ->whereIn('paid_from', ['cash', 'petty_cash'])
            ->sum('amount');

        $openingPettyCash = (float) ($deposits->sum('opening_petty_cash')
            ?: CashDeposit::query()
                ->whereDate('deposit_date', '<', $day)
                ->latest('deposit_date')
                ->value('petty_cash_kept'));

        $expenseDeductedFromCounterCash = $usesExplicitClosing ? 0.0 : $expenseTotal;
        $counterCashForExpected = $usesExplicitClosing ? $closingCashAfterExpenseAndDue : $cashCollected;
        $counterCashExpected = $openingPettyCash + $counterCashForExpected - $expenseDeductedFromCounterCash - $capitalInstallmentsPaidFromCounter;
        $counterCashCollected = (float) $deposits->sum('cash_collected_from_counter');
        $pettyCashKept = (float) $deposits->sum('petty_cash_kept');

        if ($deposits->isEmpty()) {
            $counterCashCollected = $counterCashExpected;
        } elseif ($usesManualClosing && $amountCollectedFromStaff > 0) {
            $counterCashCollected = $amountCollectedFromStaff
                + $pettyCashKept
                + $openingPettyCash;
        } elseif ($counterCashCollected <= 0 && $amountCollectedFromStaff > 0) {
            $counterCashCollected = $amountCollectedFromStaff
                + $pettyCashKept
                + $openingPettyCash;
        }

        $expectedOwnerCollection = max(0, $counterCashExpected - $openingPettyCash - $pettyCashKept);

        $capitalPaidFromCurrentClosing = $usesExplicitClosing
            ? min($capitalInstallmentsPaidFromCounter, max(0, $closingCashAfterExpenseAndDue - $cashCollected))
            : 0.0;
        $totalCollection = max(0, $cashCollected);
        $netCashProfit = $usesExplicitClosing
            ? $cashCollected + $capitalPaidFromCurrentClosing
            : $cashCollected - $expenseTotal;
        $overallCommissionRate = $this->overallStaffCommissionRate($day);
        $staffShareEstimate = $this->staffShareForNetProfit($netCashProfit, $day);
        $ownerProfitAfterStaffShare = $netCashProfit - $staffShareEstimate;
        $ownerProfitAfterCapitalInstallments = $ownerProfitAfterStaffShare - $capitalInstallmentsPaidFromBusiness;

        $report = [
            'date' => $day,
            'table_sales' => $tableSales,
            'closing_source' => $usesManualClosing ? 'manual' : ($usesGameSessionClosing ? 'game_sessions' : 'system'),
            'source_label' => $usesManualClosing
                ? 'Manual register closing'
                : ($usesGameSessionClosing ? 'Checked-out game sessions closing' : 'Game/payment records'),
            'sessions_count' => $checkedOutSessionIds->count(),
            'frames_count' => (int) GameSession::query()
                ->whereIn('id', $checkedOutSessionIds)
                ->whereIn('game_type', ['one_to_one', 'doubles'])
                ->sum('frames_played'),
            'gross_sales_total' => $grossSalesTotal,
            'sales_total' => $salesTotal,
            'due_total' => $dueTotal,
            'dues_added' => $duesAdded,
            'dues_recovered' => $duesRecovered,
            'dues_discounted' => $duesDiscounted,
            'dues_net_change' => $duesAdded - $duesRecovered - $duesDiscounted,
            'dues' => $this->duesSummary($day),
            'add_on_total' => $addOnTotal,
            'total_collection' => $totalCollection,
            'cash_collected' => $cashCollected,
            'expense_total' => $expenseTotal,
            'daily_expense_total' => $dailyExpenseTotal,
            'rent_expense_total' => $rentExpenseTotal,
            'staff_paid_total' => $staffPaidTotal,
            'capital_installments_paid' => $capitalInstallmentsPaid,
            'capital_installments_paid_from_business' => $capitalInstallmentsPaidFromBusiness,
            'capital_installments_paid_from_counter' => $capitalInstallmentsPaidFromCounter,
            'net_cash_profit' => $netCashProfit,
            'commission_rate' => $overallCommissionRate,
            'overall_commission_rate' => $overallCommissionRate,
            'staff_share_estimate' => $staffShareEstimate,
            'owner_profit_after_staff_share' => $ownerProfitAfterStaffShare,
            'owner_profit_after_capital_installments' => $ownerProfitAfterCapitalInstallments,
            'counter_cash_expected' => $counterCashExpected,
            'counter_cash_collected' => $counterCashCollected,
            'expected_owner_collection' => $expectedOwnerCollection,
            'deposits' => $deposits,
            'opening_petty_cash' => $openingPettyCash,
            'amount_collected_from_staff' => $amountCollectedFromStaff,
            'petty_cash_kept' => $pettyCashKept,
            'bank_deposit_amount' => $this->collectionDepositedToBank($day),
        ];

        if ($withCapital) {
            $report['capital'] = $this->capitalSummary($day);
            $report['liabilities'] = $this->liabilitySummary($day);
        }

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    public function monthly(Carbon|string $month, ?array $monthlyClosingOverride = null): array
    {
        $start = Carbon::parse($month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $tables = SnookerTable::query()
            ->orderBy('number')
            ->get();
        $tableNumbers = $tables
            ->pluck('number')
            ->map(fn ($number): int => (int) $number)
            ->values()
            ->all() ?: [1, 2, 3, 4];

        $tableSales = [];
        $tableSalesByNumber = collect($tableNumbers)
            ->mapWithKeys(fn (int $number): array => [$number => 0.0])
            ->all();

        foreach ($tables as $table) {
            $tableSales[$table->id] = [
                'table' => $table,
                'sessions' => 0,
                'frames' => 0,
                'sales' => 0.0,
            ];
        }

        $sessionsCount = 0;
        $framesCount = 0;
        $grossSalesTotal = 0.0;
        $salesTotal = 0.0;
        $totalCollection = 0.0;
        $cashCollected = 0.0;
        $expenseTotal = 0.0;
        $dailyExpenseTotal = 0.0;
        $addOnTotal = 0.0;
        $staffPaidTotal = 0.0;
        $capitalInstallmentsPaid = 0.0;
        $capitalInstallmentsPaidFromBusiness = 0.0;
        $duesAdded = 0.0;
        $duesRecovered = 0.0;
        $duesDiscounted = 0.0;
        $counterCashCollected = 0.0;
        $netProfit = 0.0;
        $commissionEstimate = 0.0;
        $manualDays = 0;
        $dailyRows = [];

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $daily = $this->daily($day, withCapital: false);
            $dailyTableSales = collect($tableNumbers)
                ->mapWithKeys(fn (int $number): array => [$number => 0.0])
                ->all();

            if ($daily['closing_source'] === 'manual') {
                $manualDays++;
            }

            $sessionsCount += (int) $daily['sessions_count'];
            $framesCount += (int) $daily['frames_count'];
            $grossSalesTotal += (float) $daily['gross_sales_total'];
            $salesTotal += (float) $daily['sales_total'];
            $totalCollection += (float) $daily['total_collection'];
            $cashCollected += (float) $daily['cash_collected'];
            $expenseTotal += (float) $daily['expense_total'];
            $addOnTotal += (float) $daily['add_on_total'];
            $staffPaidTotal += (float) $daily['staff_paid_total'];
            $capitalInstallmentsPaid += (float) $daily['capital_installments_paid'];
            $capitalInstallmentsPaidFromBusiness += (float) $daily['capital_installments_paid_from_business'];
            $duesAdded += (float) $daily['dues_added'];
            $duesRecovered += (float) $daily['dues_recovered'];
            $duesDiscounted += (float) $daily['dues_discounted'];
            $counterCashCollected += (float) $daily['counter_cash_collected'];
            $netProfit += (float) $daily['net_cash_profit'];
            $commissionEstimate += (float) $daily['staff_share_estimate'];

            foreach ($daily['table_sales'] as $row) {
                $tableId = $row['table']->id;

                if (! isset($tableSales[$tableId])) {
                    continue;
                }

                $tableSales[$tableId]['sessions'] += (int) $row['sessions'];
                $tableSales[$tableId]['frames'] += (int) ($row['frames'] ?? 0);
                $tableSales[$tableId]['sales'] += (float) $row['sales'];
                $tableNumber = (int) $row['table']->number;
                $dailyTableSales[$tableNumber] = (float) (($dailyTableSales[$tableNumber] ?? 0) + (float) $row['sales']);
                $tableSalesByNumber[$tableNumber] = (float) (($tableSalesByNumber[$tableNumber] ?? 0) + (float) $row['sales']);
            }

            $hasActivity = collect([
                $daily['sales_total'],
                $daily['expense_total'],
                $daily['cash_collected'],
                $daily['staff_paid_total'],
                $daily['dues_added'],
                $daily['dues_recovered'],
                $daily['dues_discounted'],
            ])->contains(fn ($amount): bool => (float) $amount > 0);

            if ($hasActivity) {
                $dailyRows[] = [
                    'date' => $day->toDateString(),
                    'table_sales_by_number' => $dailyTableSales,
                    'gross_sales_total' => (float) $daily['gross_sales_total'],
                    'sales_total' => (float) $daily['sales_total'],
                    'daily_expense_total' => (float) ($daily['daily_expense_total'] ?? $daily['expense_total']),
                    'cash_collected' => (float) $daily['cash_collected'],
                    'staff_paid_total' => (float) $daily['staff_paid_total'],
                    'dues_added' => (float) $daily['dues_added'],
                    'dues_recovered' => (float) $daily['dues_recovered'],
                    'dues_discounted' => (float) $daily['dues_discounted'],
                    'dues_net_change' => (float) $daily['dues_net_change'],
                    'source_label' => $daily['source_label'],
                ];
            }
        }

        $rentExpense = $this->rentExpenseAdjustmentBetween($start, $end, monthlyClosingOverride: $monthlyClosingOverride);
        $rentExpenseTotal = (float) $rentExpense['total'];
        $rentExpenseAlreadyCounted = (float) $rentExpense['already_counted'];
        $dailyExpenseTotal = max(0, $expenseTotal - $rentExpenseAlreadyCounted);
        $expenseTotal = $dailyExpenseTotal + $rentExpenseTotal;
        $netProfit -= (float) $rentExpense['adjustment'];
        $commissionEstimate = max(0, $commissionEstimate - (float) $rentExpense['commission_adjustment']);

        if ($netProfit <= 0) {
            $commissionEstimate = 0.0;
        }

        $depositsTotal = $this->collectionDepositedToBank($start, $end);

        $overallCommissionRate = $this->effectiveCommissionRate($netProfit, $commissionEstimate, $end);
        $staffShares = $this->staffShares($start, $end, $commissionEstimate, $overallCommissionRate);
        $staffShareRows = collect($staffShares);
        $commissionEstimate = (float) $staffShareRows->sum('monthly_share');
        $staffAdvanceCarryIn = $this->staffAdvanceCarryIntoMonth($start);
        $staffDistributionToBePaid = round($staffAdvanceCarryIn + $commissionEstimate - $staffPaidTotal, 2);
        $staffAdvanceCarryForward = round(min(0, $staffDistributionToBePaid), 2);
        $collectionAfterRent = round($cashCollected - $rentExpenseTotal, 2);
        $commissionDistributionBase = round($netProfit, 2);
        $ownerProfitAfterStaffShare = $netProfit - $commissionEstimate;
        $ownerProfitAfterCapitalInstallments = $ownerProfitAfterStaffShare - $capitalInstallmentsPaidFromBusiness;
        $capitalSummary = $this->capitalSummary($end);
        $liabilitySummary = $this->liabilitySummary($end);

        return [
            'month' => $start,
            'table_numbers' => $tableNumbers,
            'table_sales' => collect(array_values($tableSales)),
            'table_sales_by_number' => $tableSalesByNumber,
            'daily_rows' => $dailyRows,
            'sessions_count' => $sessionsCount,
            'frames_count' => $framesCount,
            'gross_sales_total' => $grossSalesTotal,
            'sales_total' => $salesTotal,
            'add_on_total' => $addOnTotal,
            'total_collection' => $totalCollection,
            'cash_collected' => $cashCollected,
            'collection_after_rent' => $collectionAfterRent,
            'counter_cash_collected' => $counterCashCollected,
            'expense_total' => $expenseTotal,
            'daily_expense_total' => $dailyExpenseTotal,
            'rent_expense_total' => $rentExpenseTotal,
            'rent_expense_adjustment' => (float) $rentExpense['adjustment'],
            'rent_expense_applied' => true,
            'monthly_closing' => $rentExpense['months'][0] ?? $this->monthlyClosingReportRow($start, $monthlyClosingOverride),
            'staff_paid_total' => $staffPaidTotal,
            'capital_installments_paid' => $capitalInstallmentsPaid,
            'capital_installments_paid_from_business' => $capitalInstallmentsPaidFromBusiness,
            'dues_added' => $duesAdded,
            'dues_recovered' => $duesRecovered,
            'dues_discounted' => $duesDiscounted,
            'dues_net_change' => $duesAdded - $duesRecovered - $duesDiscounted,
            'dues' => $this->duesSummary($end),
            'net_profit' => $netProfit,
            'commission_distribution_base' => $commissionDistributionBase,
            'bank_deposit_amount' => $depositsTotal,
            'manual_days' => $manualDays,
            'system_days' => $start->daysInMonth - $manualDays,
            'commission_rate' => $overallCommissionRate,
            'overall_commission_rate' => $overallCommissionRate,
            'commission_estimate' => $commissionEstimate,
            'staff_advance_carry_in' => $staffAdvanceCarryIn,
            'staff_advance_carry_forward' => $staffAdvanceCarryForward,
            'staff_distribution_to_be_paid' => $staffDistributionToBePaid,
            'owner_profit_after_staff_share' => $ownerProfitAfterStaffShare,
            'owner_profit_after_capital_installments' => $ownerProfitAfterCapitalInstallments,
            'capital' => $capitalSummary,
            'liabilities' => $liabilitySummary,
            'construction_deductions' => $this->constructionDeductionSummary($end),
            'staff_shares' => $staffShares,
            'staff_commission_totals' => [
                'previous_balance' => round((float) $staffShareRows->sum('previous_balance'), 2),
                'monthly_share' => round((float) $staffShareRows->sum('monthly_share'), 2),
                'monthly_commission_to_be_paid' => round((float) $staffShareRows->sum('monthly_share'), 2),
                'total_payable' => round((float) $staffShareRows->sum('total_payable'), 2),
                'advance_paid' => round((float) $staffShareRows->sum('advance_paid'), 2),
                'payout_paid' => round((float) $staffShareRows->sum('payout_paid'), 2),
                'generated_paid' => round((float) $staffShareRows->sum('paid_amount'), 2),
                'total_paid' => round((float) $staffShareRows->sum('total_paid'), 2),
                'already_paid_this_month' => round((float) $staffShareRows->sum('total_paid'), 2),
                'monthly_remaining' => round((float) $staffShareRows->sum('monthly_remaining'), 2),
                'total_to_be_paid_this_month' => round((float) $staffShareRows->sum('monthly_remaining'), 2),
                'remaining_balance' => round((float) $staffShareRows->sum('remaining_balance'), 2),
            ],
        ];
    }

    public function generateMonthlyCommission(Staff $staff, Carbon|string $month, float $paidAmount = 0): MonthlyCommission
    {
        $start = Carbon::parse($month)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $report = $this->monthly($start);

        $previousBalance = (float) (MonthlyCommission::query()
            ->where('staff_id', $staff->id)
            ->whereDate('month', '<', $start->toDateString())
            ->orderByDesc('month')
            ->value('balance_due') ?? 0);

        $staffShare = collect($report['staff_shares'])
            ->first(fn (array $row): bool => $row['staff']->is($staff));

        $advances = (float) StaffTransaction::query()
            ->where('staff_id', $staff->id)
            ->where('type', 'advance')
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');
        $payouts = (float) StaffTransaction::query()
            ->where('staff_id', $staff->id)
            ->where('type', 'payout')
            ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
            ->sum('amount');

        $commissionAmount = (float) ($staffShare['monthly_share'] ?? 0);
        $commissionRate = (float) ($staffShare['commission_rate'] ?? 0);
        $balanceDue = $previousBalance + $commissionAmount - $advances - $payouts - $paidAmount;

        return MonthlyCommission::query()->updateOrCreate(
            [
                'staff_id' => $staff->id,
                'month' => $start->toDateString(),
            ],
            [
                'cash_collected' => $report['cash_collected'],
                'expense_total' => $report['expense_total'],
                'net_profit' => $report['net_profit'],
                'commission_rate' => $commissionRate,
                'commission_amount' => round($commissionAmount, 2),
                'carried_forward_from_previous' => round($previousBalance, 2),
                'advances_deducted' => round($advances + $payouts, 2),
                'paid_amount' => round($paidAmount, 2),
                'balance_due' => round($balanceDue, 2),
                'generated_at' => now(),
            ],
        );
    }

    private function manualSalesTotal(iterable $deposits): float
    {
        return collect($deposits)->sum(
            fn (CashDeposit $deposit): float => (float) $deposit->manual_sales_total,
        );
    }

    private function manualCustomerDuesTotal(iterable $deposits): float
    {
        return collect($deposits)->sum(
            fn (CashDeposit $deposit): float => collect($deposit->customer_dues ?? [])
                ->sum(fn (array $row): float => (float) ($row['amount'] ?? 0)),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function capitalSummary(Carbon|string|null $asOf = null): array
    {
        $asOfDate = Carbon::parse($asOf ?? today())->toDateString();

        $capitalEntries = OwnerCapital::query()
            ->whereDate('entry_date', '<=', $asOfDate)
            ->get();

        $capitalInvested = (float) $capitalEntries->sum(fn (OwnerCapital $entry): float => $entry->signed_amount);
        $capitalAdded = (float) $capitalEntries
            ->where('type', 'investment')
            ->sum(fn (OwnerCapital $entry): float => (float) $entry->amount);
        $capitalReduced = (float) $capitalEntries
            ->where('type', 'capital_reduction')
            ->sum(fn (OwnerCapital $entry): float => (float) $entry->amount);
        $ownerProfitToDate = $this->ownerProfitAfterStaffShareToDate($asOfDate);
        $capitalRecovered = min(max(0, $capitalInvested), max(0, $ownerProfitToDate));
        $capitalRemaining = max(0, $capitalInvested - $capitalRecovered);
        $cashCollectedFromManager = (float) CashDeposit::query()
            ->whereDate('deposit_date', '<=', $asOfDate)
            ->sum('amount_collected_from_staff');
        $bankDeposited = (float) BankTransaction::summary($asOfDate)['daily_deposits'];

        return [
            'as_of' => $asOfDate,
            'capital_added' => round($capitalAdded, 2),
            'capital_reduced' => round($capitalReduced, 2),
            'capital_invested' => round($capitalInvested, 2),
            'owner_profit_to_date' => round($ownerProfitToDate, 2),
            'capital_recovered' => round($capitalRecovered, 2),
            'capital_remaining' => round($capitalRemaining, 2),
            'cash_collected_from_manager' => round($cashCollectedFromManager, 2),
            'bank_deposited_to_date' => round($bankDeposited, 2),
            'is_recovered' => $capitalInvested > 0 && $capitalRemaining <= 0,
        ];
    }

    /**
     * @return array<string, float|int|string>
     */
    public function bankSummary(Carbon|string|null $asOf = null): array
    {
        return BankTransaction::summary($asOf);
    }

    /**
     * @return array<string, float|string>
     */
    public function constructionDeductionSummary(Carbon|string|null $asOf = null): array
    {
        $asOfDate = Carbon::parse($asOf ?? today())->toDateString();
        $closings = MonthlyClosing::query()
            ->whereDate('month', '<=', Carbon::parse($asOfDate)->startOfMonth()->toDateString())
            ->get(['construction_deduction_amount', 'construction_received_amount']);
        $deducted = (float) $closings->sum('construction_deduction_amount');
        $received = (float) $closings->sum('construction_received_amount');

        return [
            'as_of' => $asOfDate,
            'deducted_total' => round($deducted, 2),
            'received_total' => round($received, 2),
            'balance_total' => round(max(0, $deducted - $received), 2),
        ];
    }

    private function collectionDepositedToBank(Carbon|string $start, Carbon|string|null $end = null): float
    {
        $startDate = Carbon::parse($start)->toDateString();
        $endDate = Carbon::parse($end ?? $start)->toDateString();

        return round((float) BankTransaction::query()
            ->where('type', 'daily_collection_deposit')
            ->whereDate('transaction_date', '>=', $startDate)
            ->whereDate('transaction_date', '<=', $endDate)
            ->sum('amount'), 2);
    }

    /**
     * @return array{total: float, already_counted: float, adjustment: float, commission_adjustment: float, months: array<int, array<string, mixed>>}
     */
    private function rentExpenseAdjustmentBetween(
        Carbon $start,
        Carbon $end,
        $rates = null,
        ?array $monthlyClosingOverride = null,
        bool $includeScheduledDefaults = false,
    ): array {
        $total = 0.0;
        $commissionAdjustment = 0.0;
        $months = [];

        for ($month = $start->copy()->startOfMonth(); $month->lte($end); $month->addMonth()) {
            $monthEnd = $month->copy()->endOfMonth();
            $closing = $this->monthlyClosingReportRow($month, $monthlyClosingOverride, $includeScheduledDefaults);
            $rentTotal = (float) $closing['rent_total'];

            if ($rentTotal <= 0) {
                continue;
            }

            $total += $rentTotal;
            $commissionAdjustment += $rates
                ? $this->staffShareForNetProfitUsingRates($rentTotal, $monthEnd, $rates)
                : $this->staffShareForNetProfit($rentTotal, $monthEnd);
            $months[] = $closing;
        }

        return [
            'total' => round($total, 2),
            'already_counted' => 0.0,
            'adjustment' => round($total, 2),
            'commission_adjustment' => round($commissionAdjustment, 2),
            'months' => $months,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function monthlyClosingReportRow(
        Carbon $month,
        ?array $monthlyClosingOverride = null,
        bool $includeScheduledDefaults = false,
    ): array {
        $month = $month->copy()->startOfMonth();

        if ($monthlyClosingOverride && Carbon::parse($monthlyClosingOverride['month'] ?? $month)->isSameMonth($month)) {
            $preview = [
                ...MonthlyClosing::defaultsForMonth($month),
                ...$monthlyClosingOverride,
                'month' => $month->toDateString(),
                'source' => 'preview',
            ];
            $preview['construction_balance'] = round(max(
                0,
                (float) ($preview['construction_deduction_amount'] ?? 0) - (float) ($preview['construction_received_amount'] ?? 0),
            ), 2);

            return $preview;
        }

        $closing = MonthlyClosing::forMonth($month);

        if ($closing) {
            return [
                'id' => $closing->id,
                'month' => $closing->month->toDateString(),
                'status' => $closing->status,
                'rent_total' => (float) $closing->rent_total,
                'rent_paid_amount' => (float) $closing->rent_paid_amount,
                'rent_paid_from' => $closing->rent_paid_from,
                'construction_deduction_amount' => (float) $closing->construction_deduction_amount,
                'construction_received_amount' => (float) $closing->construction_received_amount,
                'construction_account_name' => $closing->construction_account_name,
                'construction_balance' => $closing->construction_balance,
                'liabilities_verified' => (bool) $closing->liabilities_verified,
                'closed_at' => $closing->closed_at?->toDateTimeString(),
                'notes' => $closing->notes,
                'source' => 'monthly_closing',
            ];
        }

        $legacyRent = $this->legacyRentExpenseTotal($month, $month->copy()->endOfMonth());
        $defaults = MonthlyClosing::defaultsForMonth($month);

        if ($legacyRent > 0) {
            $defaults['rent_total'] = $legacyRent;
            $defaults['rent_paid_amount'] = 0.0;
            $defaults['construction_deduction_amount'] = $legacyRent;
            $defaults['source'] = 'legacy_rent_expense';
        } elseif ($includeScheduledDefaults) {
            $defaults['source'] = 'scheduled_default';
        } else {
            $defaults['rent_total'] = 0.0;
            $defaults['rent_paid_amount'] = 0.0;
            $defaults['construction_deduction_amount'] = 0.0;
            $defaults['source'] = 'none';
        }

        $defaults['construction_balance'] = max(0, (float) $defaults['construction_deduction_amount'] - (float) $defaults['construction_received_amount']);

        return $defaults;
    }

    private function legacyRentExpenseTotal(Carbon $start, Carbon $end): float
    {
        return round((float) Expense::query()
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->get(['category', 'description', 'amount'])
            ->filter(fn (Expense $expense): bool => $this->isRentExpense($expense))
            ->sum('amount'), 2);
    }

    private function isRentExpense(Expense $expense): bool
    {
        return $expense->isRent();
    }

    private function applyRentExpenseToMonthlyRow(array $row, Carbon $monthEnd, $rates): array
    {
        $rentExpense = $this->rentExpenseAdjustmentBetween($row['month']->copy()->startOfMonth(), $monthEnd, $rates);
        $rentExpenseTotal = (float) $rentExpense['total'];
        $rentExpenseAlreadyCounted = (float) $rentExpense['already_counted'];
        $dailyExpenseTotal = max(0, (float) $row['expense_total'] - $rentExpenseAlreadyCounted);

        $row['daily_expense_total'] = round($dailyExpenseTotal, 2);
        $row['rent_expense_total'] = round($rentExpenseTotal, 2);
        $row['expense_total'] = round($dailyExpenseTotal + $rentExpenseTotal, 2);
        $row['collection_after_rent'] = round((float) $row['cash_collected'] - (float) $row['rent_expense_total'], 2);
        $row['rent_expense_adjustment'] = (float) $rentExpense['adjustment'];
        $row['rent_expense_applied'] = true;
        $row['net_profit'] = round((float) $row['net_profit'] - (float) $rentExpense['adjustment'], 2);
        $row['commission_distribution_base'] = $row['net_profit'];
        $row['commission_estimate'] = max(0, round((float) $row['commission_estimate'] - (float) $rentExpense['commission_adjustment'], 2));

        if ($row['net_profit'] <= 0) {
            $row['commission_estimate'] = 0.0;
        }

        $row['commission_rate'] = $this->effectiveCommissionRateUsingRates($row['net_profit'], $row['commission_estimate'], $monthEnd, $rates);
        $row['overall_commission_rate'] = $row['commission_rate'];
        $row['owner_profit_after_staff_share'] = $row['net_profit'] - $row['commission_estimate'];
        $row['owner_profit_after_capital_installments'] = $row['owner_profit_after_staff_share'] - (float) $row['capital_installments_paid'];

        return $row;
    }

    /**
     * @return array<string, mixed>
     */
    public function liabilitySummary(Carbon|string|null $asOf = null): array
    {
        $asOfDate = Carbon::parse($asOf ?? today())->toDateString();
        $liabilities = CapitalLiability::query()
            ->whereDate('start_date', '<=', $asOfDate)
            ->get();

        $principalTotal = (float) $liabilities->sum(fn (CapitalLiability $liability): float => (float) $liability->principal_amount);
        $paidToDate = (float) CapitalLiabilityPayment::query()
            ->whereDate('payment_date', '<=', $asOfDate)
            ->sum('amount');
        $balanceTotal = max(0, $principalTotal - $paidToDate);
        $loanPrincipal = (float) $liabilities
            ->where('category', 'Loan')
            ->sum(fn (CapitalLiability $liability): float => (float) $liability->principal_amount);
        $equipmentPrincipal = max(0, $principalTotal - $loanPrincipal);
        $loanPaid = (float) CapitalLiabilityPayment::query()
            ->whereDate('payment_date', '<=', $asOfDate)
            ->whereHas(
                'capitalLiability',
                fn ($query) => $query->where('category', 'Loan'),
            )
            ->sum('amount');
        $equipmentPaid = (float) CapitalLiabilityPayment::query()
            ->whereDate('payment_date', '<=', $asOfDate)
            ->whereHas(
                'capitalLiability',
                fn ($query) => $query->where('category', '!=', 'Loan'),
            )
            ->sum('amount');

        return [
            'as_of' => $asOfDate,
            'principal_total' => round($principalTotal, 2),
            'paid_to_date' => round($paidToDate, 2),
            'balance_total' => round($balanceTotal, 2),
            'loan_principal_total' => round($loanPrincipal, 2),
            'loan_paid_to_date' => round($loanPaid, 2),
            'loan_balance_total' => round(max(0, $loanPrincipal - $loanPaid), 2),
            'equipment_principal_total' => round($equipmentPrincipal, 2),
            'equipment_paid_to_date' => round($equipmentPaid, 2),
            'equipment_balance_total' => round(max(0, $equipmentPrincipal - $equipmentPaid), 2),
            'active_count' => $liabilities->where('status', 'active')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function yearly(Carbon|string $year): array
    {
        $start = Carbon::parse($year)->startOfYear();
        $end = $start->copy()->endOfYear();

        $months = $this->yearlyMonthlySummaries($start, $end);
        $grossSalesTotal = (float) $months->sum('gross_sales_total');
        $salesTotal = (float) $months->sum('sales_total');
        $totalCollection = (float) $months->sum('total_collection');
        $cashCollected = (float) $months->sum('cash_collected');
        $collectionAfterRent = (float) $months->sum('collection_after_rent');
        $expenseTotal = (float) $months->sum('expense_total');
        $dailyExpenseTotal = (float) $months->sum('daily_expense_total');
        $rentExpenseTotal = (float) $months->sum('rent_expense_total');
        $staffPaidTotal = (float) $months->sum('staff_paid_total');
        $capitalInstallmentsPaid = (float) $months->sum('capital_installments_paid');
        $duesAdded = (float) $months->sum('dues_added');
        $duesRecovered = (float) $months->sum('dues_recovered');
        $duesDiscounted = (float) $months->sum('dues_discounted');
        $netProfit = (float) $months->sum('net_profit');
        $commissionDistributionBase = (float) $months->sum('commission_distribution_base');
        $commissionEstimate = (float) $months->sum('commission_estimate');
        $tableNumbers = $this->tableNumbers();
        $staffDistributionToBePaidTotal = (float) $months->sum(fn (array $month): float => max(0, (float) $month['staff_distribution_to_be_paid']));
        $staffAdvanceCarryForward = (float) ($months->last()['staff_advance_carry_forward'] ?? 0);
        $ownerProfitAfterStaffShare = (float) $months->sum('owner_profit_after_staff_share');
        $ownerProfitAfterCapitalInstallments = (float) $months->sum('owner_profit_after_capital_installments');
        $ownerProfitToDate = $this->ownerProfitBeforeYear($start) + $ownerProfitAfterStaffShare;

        return [
            'year' => $start,
            'months' => $months,
            'table_numbers' => $tableNumbers,
            'gross_sales_total' => $grossSalesTotal,
            'sales_total' => $salesTotal,
            'total_collection' => $totalCollection,
            'cash_collected' => $cashCollected,
            'collection_after_rent' => $collectionAfterRent,
            'expense_total' => $expenseTotal,
            'daily_expense_total' => $dailyExpenseTotal,
            'rent_expense_total' => $rentExpenseTotal,
            'staff_paid_total' => $staffPaidTotal,
            'capital_installments_paid' => $capitalInstallmentsPaid,
            'dues_added' => $duesAdded,
            'dues_recovered' => $duesRecovered,
            'dues_discounted' => $duesDiscounted,
            'dues_net_change' => $duesAdded - $duesRecovered - $duesDiscounted,
            'dues' => $this->duesSummary($end),
            'net_profit' => $netProfit,
            'commission_distribution_base' => $commissionDistributionBase,
            'commission_estimate' => $commissionEstimate,
            'staff_distribution_to_be_paid_total' => $staffDistributionToBePaidTotal,
            'staff_advance_carry_forward' => $staffAdvanceCarryForward,
            'owner_profit_after_staff_share' => $ownerProfitAfterStaffShare,
            'owner_profit_after_capital_installments' => $ownerProfitAfterCapitalInstallments,
            'capital' => $this->capitalSummaryFromOwnerProfit($end, $ownerProfitToDate),
            'liabilities' => $this->liabilitySummary($end),
            'construction_deductions' => $this->constructionDeductionSummary($end),
        ];
    }

    private function yearlyMonthlySummaries(Carbon $start, Carbon $end)
    {
        $months = collect();
        $tableNumbers = $this->tableNumbers();

        for ($month = $start->copy(); $month->lte($end); $month->addMonth()) {
            $months->put($month->format('Y-m'), $this->blankYearlyMonth($month, $tableNumbers));
        }

        $deposits = CashDeposit::query()
            ->whereBetween('deposit_date', [$start->toDateString(), $end->toDateString()])
            ->get([
                'id',
                'deposit_date',
                'closing_source',
                'manual_table_1_sale',
                'manual_table_2_sale',
                'manual_table_3_sale',
                'manual_table_4_sale',
                'manual_expense_total',
                'dues_added',
                'dues_recovered',
                'amount_collected_from_staff',
                'cash_collected_from_counter',
            ]);
        $manualDeposits = $deposits->where('closing_source', 'manual');
        $manualDates = $manualDeposits
            ->map(fn (CashDeposit $deposit): string => $deposit->deposit_date->toDateString())
            ->unique()
            ->values();
        $detailedMonths = $this->monthsNeedingDetailedYearlyCalculation($start, $end, $manualDates->all());

        $duesAddedByMonth = $this->duesAddedByMonth($start, $end);
        $duesRecoveredByMonth = $this->duesRecoveredByMonth($start, $end);
        $duesDiscountedByMonth = $this->duesDiscountedByMonth($start, $end);
        $staffPaidByMonth = $this->staffPaidByMonth($start, $end);
        $capitalPaidByMonth = $this->capitalInstallmentsPaidByMonth($start, $end);
        $capitalPaidFromCounterByDate = $this->capitalInstallmentsPaidFromCounterByDate($start, $end);
        $bankDepositsByMonth = $this->collectionDepositedToBankByMonth($start, $end);
        $rates = CommissionRate::query()
            ->active()
            ->whereDate('effective_from', '<=', $end->toDateString())
            ->orderBy('effective_from')
            ->orderBy('id')
            ->get(['effective_from', 'rate']);
        $duesToDate = $this->duesSummary($start->copy()->subDay());
        $duesAddedToDate = (float) $duesToDate['added_to_date'];
        $duesRecoveredToDate = (float) $duesToDate['recovered_to_date'];
        $duesDiscountedToDate = (float) $duesToDate['discounted_to_date'];

        $manualDeposits
            ->groupBy(fn (CashDeposit $deposit): string => $deposit->deposit_date->toDateString())
            ->each(function ($dayDeposits, string $date) use ($months, $tableNumbers, $duesAddedByMonth, $duesRecoveredByMonth, $duesDiscountedByMonth, $capitalPaidFromCounterByDate, $rates): void {
                $monthKey = Carbon::parse($date)->format('Y-m');

                if (! $months->has($monthKey)) {
                    return;
                }

                $grossSales = (float) $dayDeposits->sum(fn (CashDeposit $deposit): float => (float) $deposit->manual_sales_total);
                $expenseTotal = (float) $dayDeposits->sum('manual_expense_total');
                $duesAdded = (float) $dayDeposits->sum('dues_added');
                $duesRecovered = (float) $dayDeposits->sum('dues_recovered');
                $duesDiscounted = (float) ($duesDiscountedByMonth[$monthKey] ?? 0);
                $salesTotal = max(0, $grossSales - $duesAdded + $duesRecovered);
                $cashCollected = max(0, (float) $dayDeposits->sum('amount_collected_from_staff'));
                $manualCashAfterExpenseAndDue = max(0, $salesTotal - $expenseTotal);
                $capitalPaidFromCurrentClosing = min(
                    (float) ($capitalPaidFromCounterByDate[$date] ?? 0),
                    max(0, $manualCashAfterExpenseAndDue - $cashCollected),
                );
                $netProfit = $cashCollected + $capitalPaidFromCurrentClosing;
                $commissionEstimate = $this->staffShareForNetProfitUsingRates($netProfit, $date, $rates);
                $row = $months->get($monthKey);

                $row['manual_days']++;
                $row['gross_sales_total'] += $grossSales;
                $row['sales_total'] += $salesTotal;
                $row['total_collection'] += $cashCollected;
                $row['cash_collected'] += $cashCollected;
                $row['counter_cash_collected'] += (float) $dayDeposits->sum('cash_collected_from_counter');
                $row['expense_total'] += $expenseTotal;
                foreach ($tableNumbers as $tableNumber) {
                    $column = 'manual_table_'.$tableNumber.'_sale';
                    $row['table_sales_by_number'][$tableNumber] = (float) (($row['table_sales_by_number'][$tableNumber] ?? 0) + (float) $dayDeposits->sum($column));
                }
                $row['dues_added'] = (float) ($duesAddedByMonth[$monthKey] ?? ($row['dues_added'] + $duesAdded));
                $row['dues_recovered'] = (float) ($duesRecoveredByMonth[$monthKey] ?? ($row['dues_recovered'] + $duesRecovered));
                $row['dues_discounted'] = (float) ($duesDiscountedByMonth[$monthKey] ?? ($row['dues_discounted'] + $duesDiscounted));
                $row['net_profit'] += $netProfit;
                $row['commission_estimate'] += $commissionEstimate;

                $months->put($monthKey, $row);
            });

        foreach ($months as $monthKey => $row) {
            $monthEnd = $row['month']->copy()->endOfMonth();

            if ($detailedMonths->contains($monthKey)) {
                $row = $this->monthly($row['month']);
            } else {
                $row['staff_paid_total'] = (float) ($staffPaidByMonth[$monthKey] ?? 0);
                $row['capital_installments_paid'] = (float) ($capitalPaidByMonth[$monthKey] ?? 0);
                $row['dues_added'] = (float) ($duesAddedByMonth[$monthKey] ?? $row['dues_added']);
                $row['dues_recovered'] = (float) ($duesRecoveredByMonth[$monthKey] ?? $row['dues_recovered']);
                $row['dues_discounted'] = (float) ($duesDiscountedByMonth[$monthKey] ?? $row['dues_discounted']);
                $row['dues_net_change'] = $row['dues_added'] - $row['dues_recovered'] - $row['dues_discounted'];
                $row['bank_deposit_amount'] = (float) ($bankDepositsByMonth[$monthKey] ?? 0);
                $row['system_days'] = $row['month']->daysInMonth - $row['manual_days'];
            }

            if (! ($row['rent_expense_applied'] ?? false)) {
                $row = $this->applyRentExpenseToMonthlyRow($row, $monthEnd, $rates);
            }

            $duesAddedToDate += (float) $row['dues_added'];
            $duesRecoveredToDate += (float) $row['dues_recovered'];
            $duesDiscountedToDate += (float) $row['dues_discounted'];
            $row['dues'] = [
                'as_of' => $monthEnd->toDateString(),
                'added_to_date' => round($duesAddedToDate, 2),
                'recovered_to_date' => round($duesRecoveredToDate, 2),
                'discounted_to_date' => round($duesDiscountedToDate, 2),
                'balance_total' => round(max(0, $duesAddedToDate - $duesRecoveredToDate - $duesDiscountedToDate), 2),
            ];

            $months->put($monthKey, $this->roundYearlyMonth($row));
        }

        $carry = $this->staffAdvanceCarryIntoMonth($start);

        return $months
            ->map(function (array $row) use (&$carry): array {
                $row['staff_advance_carry_in'] = round($carry, 2);
                $row['staff_distribution_to_be_paid'] = round($carry + (float) $row['commission_estimate'] - (float) $row['staff_paid_total'], 2);
                $row['staff_advance_carry_forward'] = round(min(0, (float) $row['staff_distribution_to_be_paid']), 2);
                $carry = (float) $row['staff_advance_carry_forward'];

                return $row;
            })
            ->values();
    }

    private function blankYearlyMonth(Carbon $month, array $tableNumbers = []): array
    {
        $tableNumbers = $tableNumbers ?: [1, 2, 3, 4];

        return [
            'month' => $month->copy()->startOfMonth(),
            'table_numbers' => $tableNumbers,
            'table_sales' => collect(),
            'table_sales_by_number' => collect($tableNumbers)
                ->mapWithKeys(fn (int $number): array => [$number => 0.0])
                ->all(),
            'daily_rows' => [],
            'sessions_count' => 0,
            'frames_count' => 0,
            'gross_sales_total' => 0.0,
            'sales_total' => 0.0,
            'add_on_total' => 0.0,
            'total_collection' => 0.0,
            'cash_collected' => 0.0,
            'collection_after_rent' => 0.0,
            'counter_cash_collected' => 0.0,
            'expense_total' => 0.0,
            'daily_expense_total' => 0.0,
            'rent_expense_total' => 0.0,
            'rent_expense_adjustment' => 0.0,
            'rent_expense_applied' => false,
            'staff_paid_total' => 0.0,
            'capital_installments_paid' => 0.0,
            'capital_installments_paid_from_business' => 0.0,
            'dues_added' => 0.0,
            'dues_recovered' => 0.0,
            'dues_discounted' => 0.0,
            'dues_net_change' => 0.0,
            'dues' => ['discounted_to_date' => 0.0, 'balance_total' => 0.0],
            'net_profit' => 0.0,
            'commission_distribution_base' => 0.0,
            'bank_deposit_amount' => 0.0,
            'manual_days' => 0,
            'system_days' => $month->daysInMonth,
            'commission_rate' => CommissionRate::DEFAULT_RATE,
            'overall_commission_rate' => CommissionRate::DEFAULT_RATE,
            'commission_estimate' => 0.0,
            'staff_advance_carry_in' => 0.0,
            'staff_advance_carry_forward' => 0.0,
            'staff_distribution_to_be_paid' => 0.0,
            'owner_profit_after_staff_share' => 0.0,
            'owner_profit_after_capital_installments' => 0.0,
            'staff_shares' => [],
            'staff_commission_totals' => [],
        ];
    }

    private function roundYearlyMonth(array $row): array
    {
        foreach ([
            'gross_sales_total',
            'sales_total',
            'add_on_total',
            'total_collection',
            'cash_collected',
            'collection_after_rent',
            'counter_cash_collected',
            'expense_total',
            'daily_expense_total',
            'rent_expense_total',
            'rent_expense_adjustment',
            'staff_paid_total',
            'capital_installments_paid',
            'dues_added',
            'dues_recovered',
            'dues_discounted',
            'dues_net_change',
            'net_profit',
            'commission_distribution_base',
            'bank_deposit_amount',
            'commission_rate',
            'overall_commission_rate',
            'commission_estimate',
            'owner_profit_after_staff_share',
            'owner_profit_after_capital_installments',
        ] as $key) {
            $row[$key] = round((float) ($row[$key] ?? 0), 2);
        }

        $row['table_sales_by_number'] = collect($row['table_sales_by_number'] ?? [])
            ->map(fn ($amount): float => round((float) $amount, 2))
            ->all();

        return $row;
    }

    private function monthsNeedingDetailedYearlyCalculation(Carbon $start, Carbon $end, array $manualDates)
    {
        $manualDates = collect($manualDates)->flip();
        $months = collect();
        $addDate = function ($date) use ($manualDates, $months): void {
            if (! $date) {
                return;
            }

            $day = Carbon::parse($date)->toDateString();

            if (! $manualDates->has($day)) {
                $months->push(Carbon::parse($day)->format('Y-m'));
            }
        };

        CashDeposit::query()
            ->whereBetween('deposit_date', [$start->toDateString(), $end->toDateString()])
            ->where('closing_source', '!=', 'manual')
            ->pluck('deposit_date')
            ->each($addDate);
        GameSession::query()
            ->whereNotNull('checked_out_at')
            ->whereBetween('checked_out_at', [$start->startOfDay()->toDateTimeString(), $end->endOfDay()->toDateTimeString()])
            ->pluck('checked_out_at')
            ->each($addDate);
        Payment::query()
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->pluck('payment_date')
            ->each($addDate);
        Expense::query()
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->pluck('expense_date')
            ->each($addDate);

        return $months->unique()->values();
    }

    private function duesAddedByMonth(Carbon $start, Carbon $end): array
    {
        $ledger = CustomerDueCharge::query()
            ->whereBetween('charge_date', [$start->toDateString(), $end->toDateString()])
            ->get(['charge_date', 'amount']);
        $fallback = CashDeposit::query()
            ->whereBetween('deposit_date', [$start->toDateString(), $end->toDateString()])
            ->whereDoesntHave('customerDueCharges')
            ->get(['deposit_date', 'dues_added']);

        return $this->monthAmountMap($ledger, 'charge_date', 'amount')
            ->mergeRecursive($this->monthAmountMap($fallback, 'deposit_date', 'dues_added'))
            ->map(fn ($amount): float => round((float) collect($amount)->flatten()->sum(), 2))
            ->all();
    }

    private function duesRecoveredByMonth(Carbon $start, Carbon $end): array
    {
        $ledger = CustomerDuePayment::query()
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->get(['payment_date', 'amount']);
        $fallback = CashDeposit::query()
            ->whereBetween('deposit_date', [$start->toDateString(), $end->toDateString()])
            ->whereDoesntHave('customerDuePayments')
            ->get(['deposit_date', 'dues_recovered']);

        return $this->monthAmountMap($ledger, 'payment_date', 'amount')
            ->mergeRecursive($this->monthAmountMap($fallback, 'deposit_date', 'dues_recovered'))
            ->map(fn ($amount): float => round((float) collect($amount)->flatten()->sum(), 2))
            ->all();
    }

    private function duesDiscountedByMonth(Carbon $start, Carbon $end): array
    {
        return $this->monthAmountMap(
            CustomerDuePayment::query()
                ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
                ->get(['payment_date', 'discount_amount']),
            'payment_date',
            'discount_amount',
        )
            ->map(fn ($amount): float => round((float) collect($amount)->flatten()->sum(), 2))
            ->all();
    }

    private function staffPaidByMonth(Carbon $start, Carbon $end): array
    {
        return $this->monthAmountMap(
            StaffTransaction::query()
                ->whereIn('type', ['advance', 'payout'])
                ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
                ->get(['transaction_date', 'amount']),
            'transaction_date',
            'amount',
        )->all();
    }

    private function capitalInstallmentsPaidByMonth(Carbon $start, Carbon $end): array
    {
        return $this->monthAmountMap(
            CapitalLiabilityPayment::query()
                ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
                ->get(['payment_date', 'amount']),
            'payment_date',
            'amount',
        )->all();
    }

    private function collectionDepositedToBankByMonth(Carbon $start, Carbon $end): array
    {
        return $this->monthAmountMap(
            BankTransaction::query()
                ->where('type', 'daily_collection_deposit')
                ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
                ->get(['transaction_date', 'amount']),
            'transaction_date',
            'amount',
        )->all();
    }

    private function capitalInstallmentsPaidFromCounterByDate(Carbon $start, Carbon $end): array
    {
        return CapitalLiabilityPayment::query()
            ->whereIn('paid_from', ['cash', 'petty_cash'])
            ->whereBetween('payment_date', [$start->toDateString(), $end->toDateString()])
            ->get(['payment_date', 'amount'])
            ->groupBy(fn (CapitalLiabilityPayment $payment): string => $payment->payment_date->toDateString())
            ->map(fn ($payments): float => round((float) $payments->sum('amount'), 2))
            ->all();
    }

    private function monthAmountMap($rows, string $dateColumn, string $amountColumn)
    {
        return collect($rows)
            ->groupBy(fn ($row): string => Carbon::parse($row->{$dateColumn})->format('Y-m'))
            ->map(fn ($monthRows): float => round((float) $monthRows->sum($amountColumn), 2));
    }

    /**
     * @return array<int, int>
     */
    private function tableNumbers(): array
    {
        $numbers = SnookerTable::query()
            ->orderBy('number')
            ->pluck('number')
            ->map(fn ($number): int => (int) $number)
            ->values()
            ->all();

        return $numbers ?: [1, 2, 3, 4];
    }

    private function staffShareForNetProfitUsingRates(float $netProfit, Carbon|string $date, $rates): float
    {
        if ($netProfit <= 0) {
            return 0.0;
        }

        return round($netProfit * ($this->commissionRateFromLoadedRates($date, $rates) / 100), 2);
    }

    private function commissionRateFromLoadedRates(Carbon|string $date, $rates): float
    {
        $asOf = Carbon::parse($date)->toDateString();
        $rate = CommissionRate::DEFAULT_RATE;

        foreach ($rates as $commissionRate) {
            if ($commissionRate->effective_from->toDateString() > $asOf) {
                break;
            }

            $rate = (float) $commissionRate->rate;
        }

        return $rate;
    }

    private function effectiveCommissionRateUsingRates(float $netProfit, float $commissionEstimate, Carbon|string $fallbackDate, $rates): float
    {
        if ($netProfit <= 0) {
            return $this->commissionRateFromLoadedRates($fallbackDate, $rates);
        }

        return round(($commissionEstimate / $netProfit) * 100, 2);
    }

    private function ownerProfitBeforeYear(Carbon $start): float
    {
        $firstDate = $this->firstFinancialDate($start->toDateString());

        if (! $firstDate || Carbon::parse($firstDate)->gte($start)) {
            return 0.0;
        }

        return (float) $this->ownerProfitAfterStaffShareToDate($start->copy()->subDay()->toDateString());
    }

    private function capitalSummaryFromOwnerProfit(Carbon|string|null $asOf, float $ownerProfitToDate): array
    {
        $asOfDate = Carbon::parse($asOf ?? today())->toDateString();

        $capitalEntries = OwnerCapital::query()
            ->whereDate('entry_date', '<=', $asOfDate)
            ->get();

        $capitalInvested = (float) $capitalEntries->sum(fn (OwnerCapital $entry): float => $entry->signed_amount);
        $capitalAdded = (float) $capitalEntries
            ->where('type', 'investment')
            ->sum(fn (OwnerCapital $entry): float => (float) $entry->amount);
        $capitalReduced = (float) $capitalEntries
            ->where('type', 'capital_reduction')
            ->sum(fn (OwnerCapital $entry): float => (float) $entry->amount);
        $capitalRecovered = min(max(0, $capitalInvested), max(0, $ownerProfitToDate));
        $capitalRemaining = max(0, $capitalInvested - $capitalRecovered);
        $cashCollectedFromManager = (float) CashDeposit::query()
            ->whereDate('deposit_date', '<=', $asOfDate)
            ->sum('amount_collected_from_staff');
        $bankDeposited = (float) BankTransaction::summary($asOfDate)['daily_deposits'];

        return [
            'as_of' => $asOfDate,
            'capital_added' => round($capitalAdded, 2),
            'capital_reduced' => round($capitalReduced, 2),
            'capital_invested' => round($capitalInvested, 2),
            'owner_profit_to_date' => round($ownerProfitToDate, 2),
            'capital_recovered' => round($capitalRecovered, 2),
            'capital_remaining' => round($capitalRemaining, 2),
            'cash_collected_from_manager' => round($cashCollectedFromManager, 2),
            'bank_deposited_to_date' => round($bankDeposited, 2),
            'is_recovered' => $capitalInvested > 0 && $capitalRemaining <= 0,
        ];
    }

    /**
     * @return array<string, float|string>
     */
    public function duesSummary(Carbon|string|null $asOf = null): array
    {
        $asOfDate = Carbon::parse($asOf ?? today())->toDateString();

        $added = (float) CustomerDue::query()
            ->sum('opening_balance')
            + (float) CustomerDueCharge::query()
                ->whereDate('charge_date', '<=', $asOfDate)
                ->sum('amount')
            + (float) CashDeposit::query()
                ->whereDate('deposit_date', '<=', $asOfDate)
                ->whereDoesntHave('customerDueCharges')
                ->sum('dues_added');
        $recovered = (float) CustomerDuePayment::query()
            ->whereDate('payment_date', '<=', $asOfDate)
            ->sum('amount')
            + (float) CashDeposit::query()
                ->whereDate('deposit_date', '<=', $asOfDate)
                ->whereDoesntHave('customerDuePayments')
                ->sum('dues_recovered');
        $discounted = (float) CustomerDuePayment::query()
            ->whereDate('payment_date', '<=', $asOfDate)
            ->sum('discount_amount');

        return [
            'as_of' => $asOfDate,
            'added_to_date' => round($added, 2),
            'recovered_to_date' => round($recovered, 2),
            'discounted_to_date' => round($discounted, 2),
            'balance_total' => round(max(0, $added - $recovered - $discounted), 2),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function staffShareSummary(Carbon|string|null $asOf = null, bool $includeScheduledRent = false): array
    {
        $asOfDate = Carbon::parse($asOf ?? today())->toDateString();
        $netProfitToDate = $this->netCashProfitToDate($asOfDate, $includeScheduledRent);
        $staffSharePool = $this->staffSharePoolToDate($asOfDate, $includeScheduledRent);
        $overallCommissionRate = $this->effectiveCommissionRate($netProfitToDate, $staffSharePool, $asOfDate);
        $staff = Staff::query()
            ->active()
            ->orderBy('name')
            ->get();
        $distributionWeightTotal = $this->staffDistributionWeightTotal($staff);
        $staffCount = $staff->count();
        $staffRows = $staff
            ->map(function (Staff $staff) use ($asOfDate, $distributionWeightTotal, $staffCount, $staffSharePool, $overallCommissionRate): array {
                $distributionShare = $this->staffDistributionShare($staff, $distributionWeightTotal, $staffCount);
                $profitRate = $overallCommissionRate * $distributionShare;
                $shareEarned = $staffSharePool * $distributionShare;
                $advancePaid = (float) StaffTransaction::query()
                    ->where('staff_id', $staff->id)
                    ->where('type', 'advance')
                    ->whereDate('transaction_date', '<=', $asOfDate)
                    ->sum('amount');
                $payoutPaid = (float) StaffTransaction::query()
                    ->where('staff_id', $staff->id)
                    ->where('type', 'payout')
                    ->whereDate('transaction_date', '<=', $asOfDate)
                    ->sum('amount');
                $monthlyPaid = (float) MonthlyCommission::query()
                    ->where('staff_id', $staff->id)
                    ->whereDate('month', '<=', Carbon::parse($asOfDate)->startOfMonth()->toDateString())
                    ->sum('paid_amount');
                $amountPaid = $advancePaid + $payoutPaid + $monthlyPaid;

                return [
                    'staff' => $staff,
                    'commission_rate' => round($profitRate, 2),
                    'distribution_rate' => round($distributionShare * 100, 2),
                    'share_earned' => round($shareEarned, 2),
                    'advance_paid' => round($advancePaid, 2),
                    'payout_paid' => round($payoutPaid, 2),
                    'monthly_paid' => round($monthlyPaid, 2),
                    'amount_paid' => round($amountPaid, 2),
                    'balance_due' => round($shareEarned - $amountPaid, 2),
                ];
            });

        return [
            'as_of' => $asOfDate,
            'net_profit_to_date' => round($netProfitToDate, 2),
            'share_earned_total' => round((float) $staffRows->sum('share_earned'), 2),
            'amount_paid_total' => round((float) $staffRows->sum('amount_paid'), 2),
            'balance_due_total' => round((float) $staffRows->sum('balance_due'), 2),
            'staff_count' => $staffRows->count(),
            'staff' => $staffRows->all(),
        ];
    }

    /**
     * @return array<string, float|string>
     */
    public function businessSummary(Carbon|string|null $asOf = null, bool $includeScheduledRent = false): array
    {
        $asOfDate = Carbon::parse($asOf ?? today())->toDateString();
        $firstDate = $this->firstFinancialDate($asOfDate);

        $salesTotal = 0.0;
        $grossSalesTotal = 0.0;
        $totalCollection = 0.0;
        $duesAdded = 0.0;
        $duesRecovered = 0.0;
        $duesDiscounted = 0.0;
        $expenseTotal = 0.0;
        $netProfit = 0.0;
        $staffCommission = 0.0;

        if ($firstDate) {
            $start = Carbon::parse($firstDate);
            $end = Carbon::parse($asOfDate);

            for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
                $daily = $this->daily($day, withCapital: false);

                $grossSalesTotal += (float) $daily['gross_sales_total'];
                $salesTotal += (float) $daily['sales_total'];
                $totalCollection += (float) $daily['total_collection'];
                $duesAdded += (float) $daily['dues_added'];
                $duesRecovered += (float) $daily['dues_recovered'];
                $duesDiscounted += (float) $daily['dues_discounted'];
                $expenseTotal += (float) $daily['expense_total'];
                $netProfit += (float) $daily['net_cash_profit'];
                $staffCommission += (float) $daily['staff_share_estimate'];
            }

            $rentExpense = $this->rentExpenseAdjustmentBetween($start, $end, includeScheduledDefaults: $includeScheduledRent);
            $expenseTotal = max(0, $expenseTotal - (float) $rentExpense['already_counted']) + (float) $rentExpense['total'];
            $netProfit -= (float) $rentExpense['adjustment'];
            $staffCommission = max(0, $staffCommission - (float) $rentExpense['commission_adjustment']);

            if ($netProfit <= 0) {
                $staffCommission = 0.0;
            }
        }

        return [
            'as_of' => $asOfDate,
            'gross_sales_total' => round($grossSalesTotal, 2),
            'sales_total' => round($salesTotal, 2),
            'total_collection' => round($totalCollection, 2),
            'dues_added' => round($duesAdded, 2),
            'dues_recovered' => round($duesRecovered, 2),
            'dues_discounted' => round($duesDiscounted, 2),
            'dues_balance_total' => round((float) $this->duesSummary($asOfDate)['balance_total'], 2),
            'expense_total' => round($expenseTotal, 2),
            'staff_commission' => round($staffCommission, 2),
            'my_profit' => round($netProfit - $staffCommission, 2),
        ];
    }

    private function ownerProfitAfterStaffShareToDate(string $asOfDate): float
    {
        return $this->netCashProfitToDate($asOfDate) - $this->staffSharePoolToDate($asOfDate);
    }

    private function netCashProfitToDate(string $asOfDate, bool $includeScheduledRent = false): float
    {
        $firstDate = $this->firstFinancialDate($asOfDate);

        if (! $firstDate) {
            return 0.0;
        }

        $profit = 0.0;
        $start = Carbon::parse($firstDate);
        $end = Carbon::parse($asOfDate);

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $profit += (float) $this->daily($day, withCapital: false)['net_cash_profit'];
        }

        $rentExpense = $this->rentExpenseAdjustmentBetween($start, $end, includeScheduledDefaults: $includeScheduledRent);

        return $profit - (float) $rentExpense['adjustment'];
    }

    private function staffSharePoolToDate(string $asOfDate, bool $includeScheduledRent = false): float
    {
        $firstDate = $this->firstFinancialDate($asOfDate);

        if (! $firstDate) {
            return 0.0;
        }

        $share = 0.0;
        $start = Carbon::parse($firstDate);
        $end = Carbon::parse($asOfDate);

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $share += (float) $this->daily($day, withCapital: false)['staff_share_estimate'];
        }

        $rentExpense = $this->rentExpenseAdjustmentBetween($start, $end, includeScheduledDefaults: $includeScheduledRent);

        return round(max(0, $share - (float) $rentExpense['commission_adjustment']), 2);
    }

    private function firstFinancialDate(string $fallbackDate): ?string
    {
        $dates = collect([
            OwnerCapital::query()->min('entry_date'),
            CashDeposit::query()->min('deposit_date'),
            Payment::query()->min('payment_date'),
            Expense::query()->min('expense_date'),
            GameSession::query()->whereNotNull('checked_out_at')->min('checked_out_at'),
        ])
            ->filter()
            ->map(fn (string $date): string => Carbon::parse($date)->toDateString());

        return $dates->min() ?: $fallbackDate;
    }

    private function staffShareForNetProfit(float $netProfit, Carbon|string|null $date = null): float
    {
        if ($netProfit <= 0) {
            return 0.0;
        }

        return round($netProfit * ($this->overallStaffCommissionRate($date) / 100), 2);
    }

    private function staffAdvanceCarryIntoMonth(Carbon $month): float
    {
        $target = $month->copy()->startOfMonth();
        $firstDate = $this->firstFinancialDate($target->toDateString());

        if (! $firstDate) {
            return 0.0;
        }

        $carry = 0.0;

        for ($cursor = Carbon::parse($firstDate)->startOfMonth(); $cursor->lt($target); $cursor->addMonth()) {
            $totals = $this->monthlyCommissionAndAdvanceTotals($cursor);
            $carry = min(
                0,
                $carry + (float) $totals['commission_estimate'] - (float) $totals['staff_paid_total'],
            );
        }

        return round($carry, 2);
    }

    /**
     * @return array{commission_estimate: float, staff_paid_total: float}
     */
    private function monthlyCommissionAndAdvanceTotals(Carbon $month): array
    {
        $start = $month->copy()->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $commissionEstimate = 0.0;
        $staffPaidTotal = 0.0;
        $netProfit = 0.0;

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $daily = $this->daily($day, withCapital: false);

            $commissionEstimate += (float) $daily['staff_share_estimate'];
            $staffPaidTotal += (float) $daily['staff_paid_total'];
            $netProfit += (float) $daily['net_cash_profit'];
        }

        $rentExpense = $this->rentExpenseAdjustmentBetween($start, $end);
        $netProfit -= (float) $rentExpense['adjustment'];
        $commissionEstimate = max(0, $commissionEstimate - (float) $rentExpense['commission_adjustment']);

        if ($netProfit <= 0) {
            $commissionEstimate = 0.0;
        }

        return [
            'commission_estimate' => round($commissionEstimate, 2),
            'staff_paid_total' => round($staffPaidTotal, 2),
        ];
    }

    private function overallStaffCommissionRate(Carbon|string|null $date = null): float
    {
        return CommissionRate::rateFor($date);
    }

    private function effectiveCommissionRate(float $netProfit, float $commissionEstimate, Carbon|string|null $fallbackDate = null): float
    {
        if ($netProfit <= 0) {
            return $this->overallStaffCommissionRate($fallbackDate);
        }

        return round(($commissionEstimate / $netProfit) * 100, 2);
    }

    private function staffDistributionWeightTotal(iterable $staff): float
    {
        return (float) collect($staff)
            ->sum(fn (Staff $staff): float => max(0, (float) $staff->commission_rate));
    }

    private function staffDistributionShare(Staff $staff, float $distributionWeightTotal, int $staffCount): float
    {
        if ($distributionWeightTotal > 0) {
            return max(0, (float) $staff->commission_rate) / $distributionWeightTotal;
        }

        return $staffCount > 0 ? 1 / $staffCount : 0.0;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function staffShares(Carbon $start, Carbon $end, float $commissionPool, float $overallCommissionRate): array
    {
        $staff = Staff::query()
            ->active()
            ->orderBy('name')
            ->get();
        $distributionWeightTotal = $this->staffDistributionWeightTotal($staff);
        $staffCount = $staff->count();
        $commissionPool = max(0, $commissionPool);

        return $staff
            ->map(function (Staff $staff) use ($start, $end, $overallCommissionRate, $distributionWeightTotal, $staffCount, $commissionPool): array {
                $previousBalance = (float) (MonthlyCommission::query()
                    ->where('staff_id', $staff->id)
                    ->whereDate('month', '<', $start->toDateString())
                    ->orderByDesc('month')
                    ->value('balance_due') ?? 0);

                $advancePaid = (float) StaffTransaction::query()
                    ->where('staff_id', $staff->id)
                    ->where('type', 'advance')
                    ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
                    ->sum('amount');

                $payoutPaid = (float) StaffTransaction::query()
                    ->where('staff_id', $staff->id)
                    ->where('type', 'payout')
                    ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
                    ->sum('amount');

                $existingCommission = MonthlyCommission::query()
                    ->where('staff_id', $staff->id)
                    ->whereDate('month', $start->toDateString())
                    ->first();

                $distributionShare = $this->staffDistributionShare($staff, $distributionWeightTotal, $staffCount);
                $distributionRate = $distributionShare * 100;
                $profitRate = $overallCommissionRate * $distributionShare;
                $monthlyShare = $commissionPool * $distributionShare;
                $paidAmount = (float) ($existingCommission?->paid_amount ?? 0);
                $totalPayable = $previousBalance + $monthlyShare;
                $totalPaid = $advancePaid + $payoutPaid + $paidAmount;
                $monthlyRemaining = $monthlyShare - $totalPaid;
                $remainingBalance = $totalPayable - $totalPaid;
                $roundedMonthlyShare = round($monthlyShare, 2);
                $roundedTotalPaid = round($totalPaid, 2);
                $roundedMonthlyRemaining = round($monthlyRemaining, 2);

                return [
                    'staff' => $staff,
                    'commission_rate' => round($profitRate, 2),
                    'distribution_weight' => round((float) $staff->commission_rate, 2),
                    'distribution_rate' => round($distributionRate, 2),
                    'previous_balance' => round($previousBalance, 2),
                    'monthly_share' => $roundedMonthlyShare,
                    'monthly_commission_to_be_paid' => $roundedMonthlyShare,
                    'total_payable' => round($totalPayable, 2),
                    'staff_paid' => round($advancePaid, 2),
                    'advance_paid' => round($advancePaid, 2),
                    'payout_paid' => round($payoutPaid, 2),
                    'paid_amount' => round($paidAmount, 2),
                    'total_paid' => $roundedTotalPaid,
                    'already_paid_this_month' => $roundedTotalPaid,
                    'monthly_remaining' => $roundedMonthlyRemaining,
                    'total_to_be_paid_this_month' => $roundedMonthlyRemaining,
                    'remaining_balance' => round($remainingBalance, 2),
                ];
            })
            ->all();
    }
}
