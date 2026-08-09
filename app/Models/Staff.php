<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staff';

    protected $fillable = [
        'name',
        'phone',
        'role',
        'commission_rate',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function paymentsCollected(): HasMany
    {
        return $this->hasMany(Payment::class, 'collected_by_staff_id');
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(CashDeposit::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(StaffTransaction::class);
    }

    public function monthlyCommissions(): HasMany
    {
        return $this->hasMany(MonthlyCommission::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
