<?php

namespace App\Models;

use App\Services\GameBillingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'player_id',
        'player_name_snapshot',
        'team',
        'is_loser',
        'base_amount',
        'discount_amount',
        'add_on_amount',
        'total_due',
        'amount_paid',
        'payment_status',
        'notes',
    ];

    protected $appends = [
        'outstanding_amount',
        'player_label',
    ];

    protected function casts(): array
    {
        return [
            'is_loser' => 'boolean',
            'base_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'add_on_amount' => 'decimal:2',
            'total_due' => 'decimal:2',
            'amount_paid' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (GameParticipant $participant): void {
            if ($participant->player_id && blank($participant->player_name_snapshot)) {
                $participant->player_name_snapshot = Player::find($participant->player_id)?->name;
            }
        });

        static::saved(function (GameParticipant $participant): void {
            app(GameBillingService::class)->recalculate($participant->gameSession);
        });

        static::deleted(function (GameParticipant $participant): void {
            app(GameBillingService::class)->recalculate($participant->gameSession);
        });
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('payment_status', ['unpaid', 'partial']);
    }

    public function getOutstandingAmountAttribute(): float
    {
        return max(0, (float) $this->total_due - (float) $this->amount_paid);
    }

    public function getPlayerLabelAttribute(): string
    {
        return $this->player?->name ?? $this->player_name_snapshot ?? 'Guest player';
    }
}
