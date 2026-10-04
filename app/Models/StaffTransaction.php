<?php

namespace App\Models;

use App\Services\MonthlyClosingPreview;
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
        'saving_account',
    ];

    protected $fillable = [
        'staff_id',
        'monthly_commission_id',
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
            if ($transaction->monthly_commission_id) {
                $commission = MonthlyCommission::query()->findOrFail($transaction->monthly_commission_id);
                $transaction->staff_id = $commission->staff_id;
                $transaction->commission_month = $commission->month;
                $transaction->type = 'closing_payment';
            }
            $transactionDate = $transaction->transaction_date ?: today();
            $transaction->commission_month = $transaction->commission_month
                ? Carbon::parse($transaction->commission_month)->startOfMonth()->toDateString()
                : Carbon::parse($transactionDate)->startOfMonth()->toDateString();
        });

        static::saved(function (StaffTransaction $transaction): void {
            if ($transaction->monthly_commission_id) {
                $transaction->syncClosingPayment();
            } else {
                BankTransaction::syncFromStaffTransaction($transaction);
                $transaction->refreshMonthlyCommissionBalance();
            }
        });

        static::deleted(function (StaffTransaction $transaction): void {
            BankTransaction::deleteForSource(BankTransaction::SOURCE_STAFF_TRANSACTION, $transaction->id);
            if ($transaction->monthly_commission_id) {
                $transaction->syncClosingPayment(deleted: true);
            } else {
                $transaction->refreshMonthlyCommissionBalance();
            }
        });
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function monthlyCommission(): BelongsTo
    {
        return $this->belongsTo(MonthlyCommission::class);
    }

    private function syncClosingPayment(bool $deleted = false): void
    {
        $commission = $this->monthlyCommission()->first();
        if (! $commission) {
            return;
        }

        $commission->fill([
            'paid_amount' => $deleted ? 0 : $this->amount,
            'paid_from' => $this->paid_from === 'unrecorded' ? null : $this->paid_from,
            'paid_on' => $deleted ? null : $this->transaction_date,
            'notes' => $this->description,
        ])->save();
        app(ReportService::class)->generateMonthlyCommission(
            $commission->staff, $commission->month, asOf: $commission->period_end,
        );
        app(MonthlyClosingPreview::class)->clear();
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
        return BankTransaction::paymentSourceOptions();
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
        if ($this->paid_from === 'unrecorded') {
            return 'Not recorded';
        }

        return self::paidFromOptions()[$this->paid_from] ?? ucfirst(str_replace('_', ' ', (string) $this->paid_from));
    }

    public function refreshMonthlyCommissionBalance(): void
    {
        $affected = [];
        foreach ([$this->getRawOriginal(), $this->getAttributes()] as $attributes) {
            if (! in_array($attributes['type'] ?? null, ['advance', 'payout'], true) || empty($attributes['staff_id'])) {
                continue;
            }
            $month = Carbon::parse($attributes['commission_month'] ?? $attributes['transaction_date'] ?? today())->startOfMonth()->toDateString();
            $affected[$attributes['staff_id'].'|'.$month] = [$attributes['staff_id'], $month];
        }
        ksort($affected);
        foreach ($affected as [$staffId, $month]) {
            $staff = Staff::query()->find($staffId);
            if ($staff?->is_commissioned) {
                app(ReportService::class)->generateMonthlyCommission($staff, $month);
            }
        }
        app(MonthlyClosingPreview::class)->clear();
    }
}
