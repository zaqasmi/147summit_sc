<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Expense extends Model
{
    use HasFactory;

    public const CATEGORY_RENT = 'Rent';

    public const RENT_BASE_AMOUNT = 30000.0;

    public const RENT_BASE_MONTH = '2026-06-01';

    public const RENT_ANNUAL_INCREASE_RATE = 10.0;

    protected $fillable = [
        'cash_deposit_id',
        'expense_date',
        'staff_id',
        'category',
        'description',
        'amount',
        'paid_from',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (Expense $expense): void {
            BankTransaction::syncFromExpense($expense);
        });

        static::deleted(function (Expense $expense): void {
            BankTransaction::deleteForSource(BankTransaction::SOURCE_EXPENSE, $expense->id);
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
            ->where('source_type', BankTransaction::SOURCE_EXPENSE);
    }

    public function isRent(): bool
    {
        if ($this->category === self::CATEGORY_RENT) {
            return true;
        }

        return preg_match('/\brent\b/i', (string) $this->description) === 1;
    }

    public static function scheduledRentForDate(Carbon|string|null $date = null): float
    {
        $month = Carbon::parse($date ?? today())->startOfMonth();
        $baseMonth = Carbon::parse(self::RENT_BASE_MONTH)->startOfMonth();

        if ($month->lt($baseMonth)) {
            return 0.0;
        }

        $yearsSinceBase = $month->year - $baseMonth->year;

        if ($month->month < $baseMonth->month) {
            $yearsSinceBase--;
        }

        return round(self::RENT_BASE_AMOUNT * pow(1 + (self::RENT_ANNUAL_INCREASE_RATE / 100), max(0, $yearsSinceBase)), 2);
    }
}
