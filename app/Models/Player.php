<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'is_active',
        'notes',
    ];

    protected $appends = [
        'balance_due',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function participants(): HasMany
    {
        return $this->hasMany(GameParticipant::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getBalanceDueAttribute(): float
    {
        return (float) $this->participants()
            ->whereIn('payment_status', ['unpaid', 'partial'])
            ->get()
            ->sum(fn (GameParticipant $participant): float => $participant->outstanding_amount);
    }
}
