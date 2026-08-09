<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class CommissionRate extends Model
{
    use HasFactory;

    public const DEFAULT_RATE = 25.0;

    protected $fillable = [
        'rate',
        'effective_from',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'effective_from' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function rateFor(Carbon|string|null $date = null): float
    {
        $asOf = Carbon::parse($date ?? today())->toDateString();

        return (float) (self::query()
            ->active()
            ->whereDate('effective_from', '<=', $asOf)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->value('rate') ?? self::DEFAULT_RATE);
    }
}
