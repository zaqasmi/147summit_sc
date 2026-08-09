<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'month',
        'cash_collected',
        'expense_total',
        'net_profit',
        'commission_rate',
        'commission_amount',
        'carried_forward_from_previous',
        'advances_deducted',
        'paid_amount',
        'balance_due',
        'generated_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'date',
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
}
