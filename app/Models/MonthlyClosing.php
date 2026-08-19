<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class MonthlyClosing extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'month',
        'status',
        'rent_total',
        'rent_paid_amount',
        'rent_paid_from',
        'construction_deduction_amount',
        'construction_received_amount',
        'construction_account_name',
        'sales_total',
        'cash_collected',
        'expense_total',
        'net_profit',
        'commission_amount',
        'staff_paid_total',
        'liabilities_paid_amount',
        'liabilities_verified',
        'closed_at',
        'closed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'date',
            'rent_total' => 'decimal:2',
            'rent_paid_amount' => 'decimal:2',
            'construction_deduction_amount' => 'decimal:2',
            'construction_received_amount' => 'decimal:2',
            'sales_total' => 'decimal:2',
            'cash_collected' => 'decimal:2',
            'expense_total' => 'decimal:2',
            'net_profit' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'staff_paid_total' => 'decimal:2',
            'liabilities_paid_amount' => 'decimal:2',
            'liabilities_verified' => 'boolean',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (MonthlyClosing $closing): void {
            BankTransaction::syncFromMonthlyClosing($closing);
        });

        static::deleted(function (MonthlyClosing $closing): void {
            BankTransaction::deleteForSource(BankTransaction::SOURCE_MONTHLY_CLOSING, $closing->id);
        });
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function paidFromOptions(): array
    {
        return [
            'cash' => 'Cash from collection',
            'bank' => 'Bank',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultsForMonth(Carbon|string $month): array
    {
        $month = Carbon::parse($month)->startOfMonth();
        $rentTotal = Expense::scheduledRentForDate($month);
        $rentPaid = round($rentTotal / 2, 2);

        return [
            'month' => $month->toDateString(),
            'status' => self::STATUS_DRAFT,
            'rent_total' => $rentTotal,
            'rent_paid_amount' => $rentPaid,
            'rent_paid_from' => 'bank',
            'construction_deduction_amount' => round(max(0, $rentTotal - $rentPaid), 2),
            'construction_received_amount' => 0.0,
            'construction_account_name' => 'Construction deduction account',
            'liabilities_verified' => false,
            'notes' => null,
        ];
    }

    public static function forMonth(Carbon|string $month): ?self
    {
        return self::query()
            ->whereDate('month', Carbon::parse($month)->startOfMonth()->toDateString())
            ->first();
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function getConstructionBalanceAttribute(): float
    {
        return round(max(0, (float) $this->construction_deduction_amount - (float) $this->construction_received_amount), 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getRentPaidFromLabelAttribute(): string
    {
        return self::paidFromOptions()[$this->rent_paid_from] ?? ucfirst(str_replace('_', ' ', (string) $this->rent_paid_from));
    }
}
