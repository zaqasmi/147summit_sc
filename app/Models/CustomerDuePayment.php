<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDuePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_due_id',
        'cash_deposit_id',
        'payment_date',
        'amount',
        'discount_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (CustomerDuePayment $payment): bool => $payment->customerDue?->refreshBalance() ?? false);
        static::deleted(fn (CustomerDuePayment $payment): bool => $payment->customerDue?->refreshBalance() ?? false);
    }

    public function customerDue(): BelongsTo
    {
        return $this->belongsTo(CustomerDue::class);
    }

    public function cashDeposit(): BelongsTo
    {
        return $this->belongsTo(CashDeposit::class);
    }
}
