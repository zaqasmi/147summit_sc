<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CapitalLiabilityPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'capital_liability_id',
        'payment_date',
        'amount',
        'paid_from',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (CapitalLiabilityPayment $payment): void {
            BankTransaction::syncFromCapitalLiabilityPayment($payment);
            OwnerCapital::syncFromCapitalLiabilityPayment($payment);
        });

        static::deleted(function (CapitalLiabilityPayment $payment): void {
            BankTransaction::deleteForSource(BankTransaction::SOURCE_CAPITAL_LIABILITY_PAYMENT, $payment->id);
            OwnerCapital::deleteForSource(OwnerCapital::SOURCE_CAPITAL_LIABILITY_PAYMENT, $payment->id);
        });
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

    public function capitalLiability(): BelongsTo
    {
        return $this->belongsTo(CapitalLiability::class);
    }

    public function bankTransaction(): HasOne
    {
        return $this->hasOne(BankTransaction::class, 'source_id')
            ->where('source_type', BankTransaction::SOURCE_CAPITAL_LIABILITY_PAYMENT);
    }

    public function ownerCapital(): HasOne
    {
        return $this->hasOne(OwnerCapital::class, 'source_id')
            ->where('source_type', OwnerCapital::SOURCE_CAPITAL_LIABILITY_PAYMENT);
    }

    public function getPaidFromLabelAttribute(): string
    {
        return self::paidFromOptions()[$this->paid_from] ?? ucfirst(str_replace('_', ' ', (string) $this->paid_from));
    }
}
