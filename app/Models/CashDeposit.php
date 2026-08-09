<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CashDeposit extends Model
{
    use HasFactory;

    protected $fillable = [
        'deposit_date',
        'staff_id',
        'closing_source',
        'opening_petty_cash',
        'manual_table_1_sale',
        'manual_table_2_sale',
        'manual_table_3_sale',
        'manual_table_4_sale',
        'manual_expense_total',
        'customer_dues',
        'dues_added',
        'dues_recovered',
        'cash_collected_from_counter',
        'amount_collected_from_staff',
        'petty_cash_kept',
        'bank_deposit_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'deposit_date' => 'date',
            'opening_petty_cash' => 'decimal:2',
            'manual_table_1_sale' => 'decimal:2',
            'manual_table_2_sale' => 'decimal:2',
            'manual_table_3_sale' => 'decimal:2',
            'manual_table_4_sale' => 'decimal:2',
            'manual_expense_total' => 'decimal:2',
            'customer_dues' => 'array',
            'dues_added' => 'decimal:2',
            'dues_recovered' => 'decimal:2',
            'cash_collected_from_counter' => 'decimal:2',
            'amount_collected_from_staff' => 'decimal:2',
            'petty_cash_kept' => 'decimal:2',
            'bank_deposit_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (CashDeposit $cashDeposit): void {
            $cashDeposit->syncCustomerDueCharges();
            BankTransaction::deleteForSource(BankTransaction::SOURCE_CASH_DEPOSIT, $cashDeposit->id);
        });

        static::deleting(function (CashDeposit $cashDeposit): void {
            BankTransaction::deleteForSource(BankTransaction::SOURCE_CASH_DEPOSIT, $cashDeposit->id);

            $expenseIds = $cashDeposit->expenses()->pluck('id');

            if ($expenseIds->isNotEmpty()) {
                BankTransaction::query()
                    ->where('source_type', BankTransaction::SOURCE_EXPENSE)
                    ->whereIn('source_id', $expenseIds)
                    ->delete();
            }
        });

        static::deleted(function (CashDeposit $cashDeposit): void {
            BankTransaction::deleteForSource(BankTransaction::SOURCE_CASH_DEPOSIT, $cashDeposit->id);
        });
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function staffAdvance(): HasOne
    {
        return $this->hasOne(StaffTransaction::class)
            ->where('type', 'advance');
    }

    public function staffAdvances(): HasMany
    {
        return $this->hasMany(StaffTransaction::class)
            ->where('type', 'advance');
    }

    public function bankTransaction(): HasOne
    {
        return $this->hasOne(BankTransaction::class, 'source_id')
            ->where('source_type', BankTransaction::SOURCE_CASH_DEPOSIT);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function customerDueCharges(): HasMany
    {
        return $this->hasMany(CustomerDueCharge::class);
    }

    public function customerDuePayments(): HasMany
    {
        return $this->hasMany(CustomerDuePayment::class);
    }

    public function getManualSalesTotalAttribute(): float
    {
        return (float) $this->manual_table_1_sale
            + (float) $this->manual_table_2_sale
            + (float) $this->manual_table_3_sale
            + (float) $this->manual_table_4_sale;
    }

    public function getCashToBeCollectedAttribute(): float
    {
        return max(
            0,
            (float) $this->cash_collected_from_counter
                - (float) $this->petty_cash_kept,
        );
    }

    private function syncCustomerDueCharges(): void
    {
        $affectedCustomerDueIds = $this->customerDueCharges()
            ->pluck('customer_due_id')
            ->all();

        $this->customerDueCharges()->delete();

        collect($this->customer_dues ?? [])
            ->map(fn (array $row): array => [
                'customer_name' => trim((string) ($row['customer_name'] ?? '')),
                'amount' => round((float) ($row['amount'] ?? 0), 2),
            ])
            ->filter(fn (array $row): bool => $row['customer_name'] !== '' && $row['amount'] > 0)
            ->groupBy(fn (array $row): string => mb_strtolower($row['customer_name']))
            ->each(function ($rows): void {
                $firstRow = $rows->first();
                $customerDue = CustomerDue::findOrCreateByName($firstRow['customer_name']);

                $this->customerDueCharges()->create([
                    'customer_due_id' => $customerDue->id,
                    'charge_date' => $this->deposit_date?->toDateString() ?? today()->toDateString(),
                    'amount' => round((float) $rows->sum('amount'), 2),
                    'notes' => 'Daily closing due',
                ]);
            });

        $this->customerDueCharges()
            ->pluck('customer_due_id')
            ->merge($affectedCustomerDueIds)
            ->unique()
            ->each(fn (int $customerDueId): bool => CustomerDue::query()->find($customerDueId)?->refreshBalance() ?? false);
    }
}
