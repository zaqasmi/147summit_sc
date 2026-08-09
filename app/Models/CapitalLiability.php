<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CapitalLiability extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_date',
        'title',
        'source_type',
        'lender_name',
        'category',
        'principal_amount',
        'installment_amount',
        'installment_frequency',
        'due_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'principal_amount' => 'decimal:2',
            'installment_amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public static function sourceOptions(): array
    {
        return [
            'bank' => 'Bank',
            'friend' => 'Friend',
            'supplier' => 'Supplier',
            'owner' => 'Owner',
            'other' => 'Other',
        ];
    }

    public static function categoryOptions(): array
    {
        return [
            'Loan' => 'Loan',
            'Solar' => 'Solar',
            'ACs' => 'ACs',
            'Furniture' => 'Furniture',
            'Renovation' => 'Renovation',
            'Equipment' => 'Equipment',
            'Other' => 'Other',
        ];
    }

    public static function frequencyOptions(): array
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'custom' => 'Custom',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'active' => 'Active',
            'paid' => 'Paid',
            'paused' => 'Paused',
            'cancelled' => 'Cancelled',
        ];
    }

    public function payments(): HasMany
    {
        return $this->hasMany(CapitalLiabilityPayment::class);
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function getBalanceAmountAttribute(): float
    {
        return max(0, (float) $this->principal_amount - $this->paid_amount);
    }

    public function getSourceLabelAttribute(): string
    {
        return self::sourceOptions()[$this->source_type] ?? ucfirst((string) $this->source_type);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusOptions()[$this->status] ?? ucfirst((string) $this->status);
    }
}
