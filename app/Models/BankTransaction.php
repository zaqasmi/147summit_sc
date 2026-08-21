<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class BankTransaction extends Model
{
    use HasFactory;

    public const SOURCE_CASH_DEPOSIT = 'cash_deposit';

    public const SOURCE_CAPITAL_LIABILITY_PAYMENT = 'capital_liability_payment';

    public const SOURCE_EXPENSE = 'expense';

    public const SOURCE_MONTHLY_CLOSING = 'monthly_closing';

    public const SOURCE_OPENING_BANK_BALANCE = 'opening_bank_balance';

    public const SOURCE_OPENING_PENDING_CASH = 'opening_pending_cash';

    public const SOURCE_STAFF_TRANSACTION = 'staff_transaction';

    private const INFLOW_TYPES = [
        'daily_collection_deposit',
        'other_payment_received',
        'loan_received',
        'owner_deposit',
        'adjustment_in',
    ];

    private const OUTFLOW_TYPES = [
        'supplier_installment_paid',
        'loan_installment_paid',
        'capital_installment_paid',
        'expense_paid',
        'rent_paid',
        'supplier_payment',
        'staff_payment',
        'withdrawal',
        'adjustment_out',
    ];

    private const PENDING_CASH_TYPES = [
        'cash_pending_adjustment_in',
        'cash_pending_adjustment_out',
    ];

    private const DEPOSIT_SLIP_TYPES = [
        'daily_collection_deposit',
    ];

    protected $fillable = [
        'transaction_date',
        'type',
        'amount',
        'deposit_slip_number',
        'deposit_slip_date',
        'source_type',
        'source_id',
        'description',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'deposit_slip_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (BankTransaction $transaction): void {
            if (! self::usesDepositSlip($transaction->type)) {
                $transaction->deposit_slip_number = null;
                $transaction->deposit_slip_date = null;
            }
        });
    }

    public static function typeOptions(): array
    {
        return [
            'daily_collection_deposit' => 'Cash collection deposit',
            'other_payment_received' => 'Other payment received',
            'loan_received' => 'Bank loan received',
            'owner_deposit' => 'Owner deposit',
            'supplier_installment_paid' => 'Supplier installment paid',
            'loan_installment_paid' => 'Loan return / installment paid',
            'capital_installment_paid' => 'Capital installment paid',
            'expense_paid' => 'Expense paid from bank',
            'rent_paid' => 'Monthly rent paid',
            'supplier_payment' => 'Supplier payment',
            'staff_payment' => 'Staff payment',
            'withdrawal' => 'Bank withdrawal',
            'adjustment_in' => 'Adjustment in',
            'adjustment_out' => 'Adjustment out',
            'cash_pending_adjustment_in' => 'Pending cash adjustment in',
            'cash_pending_adjustment_out' => 'Pending cash adjustment out',
        ];
    }

    public static function creditTypeOptions(): array
    {
        return array_intersect_key(self::typeOptions(), array_flip(self::INFLOW_TYPES));
    }

    public static function debitTypeOptions(): array
    {
        return array_intersect_key(self::typeOptions(), array_flip(self::OUTFLOW_TYPES));
    }

    public static function pendingCashTypeOptions(): array
    {
        return array_intersect_key(self::typeOptions(), array_flip(self::PENDING_CASH_TYPES));
    }

    public static function typeOptionsForEntrySide(?string $entrySide): array
    {
        return match ($entrySide) {
            'debit' => self::debitTypeOptions(),
            'cash' => self::pendingCashTypeOptions(),
            default => self::creditTypeOptions(),
        };
    }

    public static function defaultTypeForEntrySide(?string $entrySide): string
    {
        return array_key_first(self::typeOptionsForEntrySide($entrySide)) ?? 'daily_collection_deposit';
    }

    public static function entrySideForType(?string $type): string
    {
        if (in_array($type, self::PENDING_CASH_TYPES, true)) {
            return 'cash';
        }

        return in_array($type, self::OUTFLOW_TYPES, true) ? 'debit' : 'credit';
    }

    public static function usesDepositSlip(?string $type): bool
    {
        return in_array($type, self::DEPOSIT_SLIP_TYPES, true);
    }

    public static function syncFromCashDeposit(CashDeposit $deposit): void
    {
        self::deleteForSource(self::SOURCE_CASH_DEPOSIT, $deposit->id);
    }

    public static function syncFromCapitalLiabilityPayment(CapitalLiabilityPayment $payment): void
    {
        if ($payment->paid_from !== 'bank' || (float) $payment->amount <= 0) {
            self::deleteForSource(self::SOURCE_CAPITAL_LIABILITY_PAYMENT, $payment->id);

            return;
        }

        $payment->loadMissing('capitalLiability');
        $liability = $payment->capitalLiability;

        self::query()->updateOrCreate(
            [
                'source_type' => self::SOURCE_CAPITAL_LIABILITY_PAYMENT,
                'source_id' => $payment->id,
            ],
            [
                'transaction_date' => $payment->payment_date?->toDateString() ?? today()->toDateString(),
                'type' => self::installmentTypeFor($liability),
                'amount' => round((float) $payment->amount, 2),
                'description' => trim('Bank installment: '.($liability?->title ?? '')),
                'notes' => $payment->notes,
            ],
        );
    }

    public static function syncCapitalLiabilityPaymentLedger(): void
    {
        $payments = CapitalLiabilityPayment::query()
            ->with('capitalLiability')
            ->get();

        $paymentIds = $payments->pluck('id')->all();

        foreach ($payments as $payment) {
            self::syncFromCapitalLiabilityPayment($payment);
        }

        $staleTransactions = self::query()
            ->where('source_type', self::SOURCE_CAPITAL_LIABILITY_PAYMENT);

        if ($paymentIds === []) {
            $staleTransactions->delete();

            return;
        }

        $staleTransactions
            ->whereNotIn('source_id', $paymentIds)
            ->delete();
    }

    public static function syncFromExpense(Expense $expense): void
    {
        if ($expense->isRent() || $expense->paid_from !== 'bank' || (float) $expense->amount <= 0) {
            self::deleteForSource(self::SOURCE_EXPENSE, $expense->id);

            return;
        }

        self::query()->updateOrCreate(
            [
                'source_type' => self::SOURCE_EXPENSE,
                'source_id' => $expense->id,
            ],
            [
                'transaction_date' => $expense->expense_date?->toDateString() ?? today()->toDateString(),
                'type' => 'expense_paid',
                'amount' => round((float) $expense->amount, 2),
                'description' => trim('Bank expense: '.$expense->description),
                'notes' => $expense->notes,
            ],
        );
    }

    public static function syncFromMonthlyClosing(MonthlyClosing $closing): void
    {
        if (
            $closing->status !== MonthlyClosing::STATUS_CLOSED
            || $closing->rent_paid_from !== 'bank'
            || (float) $closing->rent_paid_amount <= 0
        ) {
            self::deleteForSource(self::SOURCE_MONTHLY_CLOSING, $closing->id);

            return;
        }

        self::query()->updateOrCreate(
            [
                'source_type' => self::SOURCE_MONTHLY_CLOSING,
                'source_id' => $closing->id,
            ],
            [
                'transaction_date' => $closing->month?->copy()->endOfMonth()->toDateString() ?? today()->toDateString(),
                'type' => 'rent_paid',
                'amount' => round((float) $closing->rent_paid_amount, 2),
                'description' => 'Monthly rent paid: '.$closing->month?->format('F Y'),
                'notes' => $closing->notes,
            ],
        );
    }

    public static function syncFromStaffTransaction(StaffTransaction $transaction): void
    {
        if (! StaffTransaction::isBankPaidSource($transaction->paid_from) || (float) $transaction->amount <= 0) {
            self::deleteForSource(self::SOURCE_STAFF_TRANSACTION, $transaction->id);

            return;
        }

        $transaction->loadMissing('staff');
        $staffName = $transaction->staff?->name;
        $paidFromLabel = $transaction->paid_from_label;
        $description = filled($transaction->description)
            ? $transaction->description
            : trim('Staff payment'.($staffName ? ': '.$staffName : ''));

        self::query()->updateOrCreate(
            [
                'source_type' => self::SOURCE_STAFF_TRANSACTION,
                'source_id' => $transaction->id,
            ],
            [
                'transaction_date' => $transaction->transaction_date?->toDateString() ?? today()->toDateString(),
                'type' => 'staff_payment',
                'amount' => round((float) $transaction->amount, 2),
                'description' => $description,
                'notes' => 'Paid from '.$paidFromLabel,
            ],
        );
    }

    public static function deleteForSource(string $sourceType, int|string|null $sourceId): void
    {
        if (! $sourceId) {
            return;
        }

        self::query()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->delete();
    }

    /**
     * @return array<string, float|int|string>
     */
    public static function summary(Carbon|string|null $asOf = null): array
    {
        self::syncCapitalLiabilityPaymentLedger();

        $asOfDate = Carbon::parse($asOf ?? today())->toDateString();
        $asOfMonth = Carbon::parse($asOfDate)->startOfMonth()->toDateString();
        $transactions = self::query()
            ->whereDate('transaction_date', '<=', $asOfDate)
            ->get(['type', 'amount']);

        $sum = fn (array $types): float => round((float) $transactions
            ->whereIn('type', $types)
            ->sum(fn (BankTransaction $transaction): float => (float) $transaction->amount), 2);

        $dailyDeposits = $sum(['daily_collection_deposit']);
        $pendingCashAdjustmentIn = $sum(['cash_pending_adjustment_in']);
        $pendingCashAdjustmentOut = $sum(['cash_pending_adjustment_out']);
        $cashCollectedFromClosings = round((float) CashDeposit::query()
            ->whereDate('deposit_date', '<=', $asOfDate)
            ->sum('amount_collected_from_staff'), 2);
        $cashStaffPayments = round((float) StaffTransaction::query()
            ->where('paid_from', 'cash')
            ->whereNull('cash_deposit_id')
            ->whereIn('type', ['advance', 'payout'])
            ->whereDate('transaction_date', '<=', $asOfDate)
            ->sum('amount'), 2);
        $cashRentPayments = round((float) MonthlyClosing::query()
            ->where('rent_paid_from', 'cash')
            ->whereDate('month', '<=', $asOfMonth)
            ->sum('rent_paid_amount'), 2);
        $cashExpensePayments = round((float) Expense::query()
            ->where('paid_from', 'cash')
            ->whereNull('cash_deposit_id')
            ->whereDate('expense_date', '<=', $asOfDate)
            ->get(['category', 'description', 'amount'])
            ->reject(fn (Expense $expense): bool => $expense->isRent())
            ->sum('amount'), 2);
        $cashInstallmentPayments = round((float) CapitalLiabilityPayment::query()
            ->where('paid_from', 'cash')
            ->whereDate('payment_date', '<=', $asOfDate)
            ->sum('amount'), 2);
        $constructionOtherAccountReceipts = round((float) MonthlyClosing::query()
            ->whereDate('month', '<=', $asOfMonth)
            ->sum('construction_received_amount'), 2);
        $cashOutflowDeductions = round($cashStaffPayments + $cashRentPayments + $cashExpensePayments + $cashInstallmentPayments + $constructionOtherAccountReceipts, 2);
        $collectionCashPendingDeposit = round(max(0, $cashCollectedFromClosings + $pendingCashAdjustmentIn - $cashOutflowDeductions - $dailyDeposits - $pendingCashAdjustmentOut), 2);
        $otherPaymentsReceived = $sum(['other_payment_received']);
        $loanReceived = $sum(['loan_received']);
        $ownerDeposits = $sum(['owner_deposit']);
        $adjustmentsIn = $sum(['adjustment_in']);
        $supplierInstallments = $sum(['supplier_installment_paid']);
        $loanInstallments = $sum(['loan_installment_paid']);
        $capitalInstallments = $sum(['capital_installment_paid']);
        $expensesPaid = $sum(['expense_paid']);
        $rentPaid = $sum(['rent_paid']);
        $supplierPayments = $sum(['supplier_payment']);
        $staffPayments = $sum(['staff_payment']);
        $withdrawals = $sum(['withdrawal']);
        $adjustmentsOut = $sum(['adjustment_out']);
        $totalInflows = round($dailyDeposits + $otherPaymentsReceived + $loanReceived + $ownerDeposits + $adjustmentsIn, 2);
        $installmentsPaid = round($supplierInstallments + $loanInstallments + $capitalInstallments, 2);
        $nonLoanInstallmentsPaid = round($supplierInstallments + $capitalInstallments, 2);
        $totalOutflows = round($installmentsPaid + $expensesPaid + $rentPaid + $supplierPayments + $staffPayments + $withdrawals + $adjustmentsOut, 2);

        return [
            'as_of' => $asOfDate,
            'transaction_count' => $transactions->count(),
            'cash_in_bank' => round($totalInflows - $totalOutflows, 2),
            'cash_collected_from_closings' => $cashCollectedFromClosings,
            'cash_staff_payments_pending_deduction' => $cashStaffPayments,
            'cash_rent_payments_pending_deduction' => $cashRentPayments,
            'cash_expenses_pending_deduction' => $cashExpensePayments,
            'cash_installments_pending_deduction' => $cashInstallmentPayments,
            'construction_other_account_pending_deduction' => $constructionOtherAccountReceipts,
            'cash_outflow_pending_deductions' => $cashOutflowDeductions,
            'pending_cash_adjustment_in' => $pendingCashAdjustmentIn,
            'pending_cash_adjustment_out' => $pendingCashAdjustmentOut,
            'collection_cash_pending_deposit' => $collectionCashPendingDeposit,
            'daily_deposits' => $dailyDeposits,
            'other_payments_received' => $otherPaymentsReceived,
            'loan_received' => $loanReceived,
            'owner_deposits' => $ownerDeposits,
            'adjustments_in' => $adjustmentsIn,
            'supplier_installments_paid' => $supplierInstallments,
            'loan_installments_paid' => $loanInstallments,
            'capital_installments_paid' => $capitalInstallments,
            'installments_paid' => $installmentsPaid,
            'non_loan_installments_paid' => $nonLoanInstallmentsPaid,
            'expenses_paid' => $expensesPaid,
            'rent_paid' => $rentPaid,
            'supplier_payments' => $supplierPayments,
            'staff_payments' => $staffPayments,
            'withdrawals' => $withdrawals,
            'adjustments_out' => $adjustmentsOut,
            'total_inflows' => $totalInflows,
            'total_outflows' => $totalOutflows,
        ];
    }

    public static function typeColor(?string $type): string
    {
        return match ($type) {
            'daily_collection_deposit', 'other_payment_received', 'loan_received', 'owner_deposit', 'adjustment_in' => 'success',
            'supplier_installment_paid', 'loan_installment_paid', 'capital_installment_paid', 'staff_payment' => 'warning',
            'expense_paid', 'rent_paid', 'supplier_payment', 'withdrawal', 'adjustment_out' => 'danger',
            'cash_pending_adjustment_in', 'cash_pending_adjustment_out' => 'info',
            default => 'gray',
        };
    }

    public static function sourceOptions(): array
    {
        return [
            self::SOURCE_CASH_DEPOSIT => 'Daily closing',
            self::SOURCE_CAPITAL_LIABILITY_PAYMENT => 'Capital installment',
            self::SOURCE_EXPENSE => 'Expense',
            self::SOURCE_MONTHLY_CLOSING => 'Monthly closing',
            self::SOURCE_OPENING_BANK_BALANCE => 'Opening bank balance',
            self::SOURCE_OPENING_PENDING_CASH => 'Opening pending cash',
            self::SOURCE_STAFF_TRANSACTION => 'Staff transaction',
        ];
    }

    public function getSignedAmountAttribute(): float
    {
        $amount = (float) $this->amount;

        if (in_array($this->type, self::PENDING_CASH_TYPES, true)) {
            return 0.0;
        }

        return in_array($this->type, self::OUTFLOW_TYPES, true) ? -1 * $amount : $amount;
    }

    public function getDirectionAttribute(): string
    {
        if (in_array($this->type, self::PENDING_CASH_TYPES, true)) {
            return 'cash';
        }

        return in_array($this->type, self::OUTFLOW_TYPES, true) ? 'out' : 'in';
    }

    public function getEntrySideAttribute(): string
    {
        return self::entrySideForType($this->type);
    }

    public function getEntrySideLabelAttribute(): string
    {
        return match ($this->entry_side) {
            'debit' => 'Debit',
            'cash' => 'Pending cash',
            default => 'Credit',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return self::typeOptions()[$this->type] ?? ucfirst(str_replace('_', ' ', (string) $this->type));
    }

    public function getSourceLabelAttribute(): string
    {
        if (! $this->source_type || ! $this->source_id) {
            return 'Manual';
        }

        $source = self::sourceOptions()[$this->source_type] ?? ucfirst(str_replace('_', ' ', $this->source_type));

        return $source.' #'.$this->source_id;
    }

    private static function installmentTypeFor(?CapitalLiability $liability): string
    {
        if ($liability?->category === 'Loan') {
            return 'loan_installment_paid';
        }

        if ($liability?->source_type === 'supplier') {
            return 'supplier_installment_paid';
        }

        return 'capital_installment_paid';
    }
}
