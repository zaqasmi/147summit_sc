<?php

namespace App\Models;

use App\Services\ReportService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class StaffTransaction extends Model
{
    use HasFactory;

    private const BANK_PAID_SOURCES = [
        'bank',
        'easy_paisa',
        'other_bank',
    ];

    protected $fillable = [
        'staff_id',
        'cash_deposit_id',
        'transaction_date',
        'commission_month',
        'type',
        'paid_from',
        'amount',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'commission_month' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (StaffTransaction $transaction): void {
            $transactionDate = $transaction->transaction_date ?: today();
            $transaction->commission_month = $transaction->commission_month
                ? Carbon::parse($transaction->commission_month)->startOfMonth()->toDateString()
                : Carbon::parse($transactionDate)->startOfMonth()->toDateString();
        });

        static::saved(function (StaffTransaction $transaction): void {
            BankTransaction::syncFromStaffTransaction($transaction);
            $transaction->refreshMonthlyCommissionBalance();
        });

        static::deleted(function (StaffTransaction $transaction): void {
            BankTransaction::deleteForSource(BankTransaction::SOURCE_STAFF_TRANSACTION, $transaction->id);
            $transaction->refreshMonthlyCommissionBalance();
        });
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function cashDeposit(): BelongsTo
    {
        return $this->belongsTo(CashDeposit::class);
    }

    public function bankTransaction(): HasOne
    {
        return $this->hasOne(BankTransaction::class, 'source_id')
            ->where('source_type', BankTransaction::SOURCE_STAFF_TRANSACTION);
    }

    public static function paidFromOptions(): array
    {
        return [
            'cash' => 'Cash from collection',
            'bank' => 'Bank',
        ];
    }

    public static function isBankPaidSource(?string $source): bool
    {
        return in_array($source, self::BANK_PAID_SOURCES, true);
    }

    public function scopeForCommissionMonth(Builder $query, Carbon|string $month): Builder
    {
        $start = Carbon::parse($month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        return $query->where(function (Builder $query) use ($start, $end): void {
            $query
                ->where(function (Builder $query) use ($start, $end): void {
                    $query
                        ->whereDate('commission_month', '>=', $start->toDateString())
                        ->whereDate('commission_month', '<=', $end->toDateString());
                })
                ->orWhere(function (Builder $query) use ($start, $end): void {
                    $query
                        ->whereNull('commission_month')
                        ->whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()]);
                });
        });
    }

    public function getPaidFromLabelAttribute(): string
    {
        return self::paidFromOptions()[$this->paid_from] ?? ucfirst(str_replace('_', ' ', (string) $this->paid_from));
    }

    public function refreshMonthlyCommissionBalance(): void
    {
        if (! in_array($this->type, ['advance', 'payout'], true)) {
            return;
        }

        $staff = $this->staff()->first();

        if (! $staff?->is_commissioned) {
            return;
        }

        app(ReportService::class)->generateMonthlyCommission(
            $staff,
            $this->commission_month ?: $this->transaction_date ?: today(),
        );
    }
}
