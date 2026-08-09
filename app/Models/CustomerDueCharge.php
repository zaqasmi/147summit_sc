<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDueCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_due_id',
        'cash_deposit_id',
        'charge_date',
        'amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'charge_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (CustomerDueCharge $charge): bool => $charge->customerDue?->refreshBalance() ?? false);
        static::deleted(fn (CustomerDueCharge $charge): bool => $charge->customerDue?->refreshBalance() ?? false);
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
