<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MonthlyCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'month',
        'period_end',
        'cash_collected',
        'expense_total',
        'net_profit',
        'commission_rate',
        'commission_amount',
        'carried_forward_from_previous',
        'advances_deducted',
        'paid_amount',
        'paid_from',
        'paid_on',
        'balance_due',
        'generated_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'date',
            'paid_on' => 'date',
            'period_end' => 'date',
            'cash_collected' => 'decimal:2',
            'expense_total' => 'decimal:2',
            'net_profit' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'carried_forward_from_previous' => 'decimal:2',
            'advances_deducted' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'generated_at' => 'datetime',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    protected static function booted(): void
    {
        static::saving(function (MonthlyCommission $commission): void {
            if ((float) $commission->paid_amount <= 0) {
                $commission->paid_on = null;
            } elseif ($commission->paid_from && ! $commission->paid_on) {
                $commission->paid_on = today();
            }
        });
        static::saved(function (MonthlyCommission $commission): void {
            BankTransaction::syncFromMonthlyCommission($commission);
            $commission->syncStaffPayment();
        });
        static::deleted(fn (MonthlyCommission $commission) => BankTransaction::deleteForSource(BankTransaction::SOURCE_MONTHLY_COMMISSION, $commission->id));
    }

    public function staffPayment(): HasOne
    {
        return $this->hasOne(StaffTransaction::class, 'monthly_commission_id');
    }

    public function syncStaffPayment(): void
    {
        StaffTransaction::withoutEvents(function (): void {
            if ((float) $this->paid_amount <= 0) {
                $this->staffPayment()->delete();

                return;
            }

            StaffTransaction::query()->updateOrCreate(['monthly_commission_id' => $this->id], [
                'staff_id' => $this->staff_id,
                'commission_month' => $this->month->copy()->startOfMonth()->toDateString(),
                'transaction_date' => $this->paid_on ?: $this->month->copy()->endOfMonth(),
                'type' => 'closing_payment',
                'paid_from' => $this->paid_from ?: 'unrecorded',
                'amount' => $this->paid_amount,
                'description' => $this->notes ?: 'Monthly closing commission payment',
            ]);
        });
    }

    public function getPaidFromLabelAttribute(): string
    {
        return StaffTransaction::paidFromOptions()[$this->paid_from] ?? 'Not recorded';
    }

    public function getTotalPayableAttribute(): float
    {
        return round((float) $this->carried_forward_from_previous + (float) $this->commission_amount, 2);
    }

    public function getTotalPaidAttribute(): float
    {
        return round((float) $this->advances_deducted + (float) $this->paid_amount, 2);
    }

    public function getMonthlyRemainingAttribute(): float
    {
        return round((float) $this->commission_amount - (float) $this->advances_deducted - (float) $this->paid_amount, 2);
    }

    public function getBalanceStatusAttribute(): string
    {
        return (float) $this->balance_due > 0 ? 'Due' : 'Paid / advance';
    }
}
