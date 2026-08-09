<?php

namespace App\Models;

use App\Services\GameBillingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'game_participant_id',
        'player_id',
        'collected_by_staff_id',
        'payment_date',
        'payment_method',
        'amount',
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
        static::saving(function (Payment $payment): void {
            if (! $payment->payment_date) {
                $payment->payment_date = today();
            }

            if ($payment->game_participant_id) {
                $participant = GameParticipant::find($payment->game_participant_id);

                if ($participant) {
                    $payment->game_session_id = $participant->game_session_id;
                    $payment->player_id = $participant->player_id;
                }
            }
        });

        static::saved(function (Payment $payment): void {
            app(GameBillingService::class)->recalculate($payment->gameSession);
        });

        static::deleted(function (Payment $payment): void {
            app(GameBillingService::class)->recalculate($payment->gameSession);
        });
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(GameParticipant::class, 'game_participant_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'collected_by_staff_id');
    }
}
