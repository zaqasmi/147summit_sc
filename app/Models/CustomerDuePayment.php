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
        'payment_method',
        'amount',
        'discount_amount',
        'notes',
    ];

    protected $attributes = ['payment_method' => 'cash'];

    public function getPaymentMethodLabelAttribute(): string
    {
        return Payment::methodOptions()[$this->payment_method] ?? ucfirst((string) $this->payment_method);
    }

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
        static::saved(function (CustomerDuePayment $payment): void {
            BankTransaction::syncFromReceipt($payment);
            $payment->customerDue?->refreshBalance();
        });

        static::deleted(function (CustomerDuePayment $payment): void {
            BankTransaction::deleteForSource(BankTransaction::SOURCE_CUSTOMER_DUE_PAYMENT, $payment->id);
            $payment->customerDue?->refreshBalance();
        });
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
