<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerDue extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_name',
        'phone',
        'opening_balance',
        'total_charged',
        'total_paid',
        'balance_due',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'total_charged' => 'decimal:2',
            'total_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn (CustomerDue $customerDue): bool => $customerDue->refreshBalance());
    }

    public function charges(): HasMany
    {
        return $this->hasMany(CustomerDueCharge::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CustomerDuePayment::class);
    }

    public function scopeWithBalance(Builder $query): Builder
    {
        return $query->where('balance_due', '>', 0);
    }

    public static function findOrCreateByName(string $customerName): self
    {
        $customerName = trim($customerName);

        return self::query()
            ->whereRaw('lower(customer_name) = ?', [mb_strtolower($customerName)])
            ->first()
            ?? self::query()->create([
                'customer_name' => $customerName,
                'opening_balance' => 0,
            ]);
    }

    public function refreshBalance(): bool
    {
        if (! $this->exists) {
            return false;
        }

        $charged = (float) $this->charges()->sum('amount');
        $paid = (float) $this->payments()->sum('amount');
        $totalDue = (float) $this->opening_balance + $charged;

        return $this->forceFill([
            'total_charged' => round($charged, 2),
            'total_paid' => round($paid, 2),
            'balance_due' => round(max(0, $totalDue - $paid), 2),
        ])->saveQuietly();
    }
}
