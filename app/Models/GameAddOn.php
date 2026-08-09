<?php

namespace App\Models;

use App\Services\GameBillingService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameAddOn extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_session_id',
        'add_on_item_id',
        'item_name',
        'unit_price',
        'quantity',
        'total_amount',
        'charged_to',
        'player_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (GameAddOn $addOn): void {
            if ($addOn->add_on_item_id) {
                $item = AddOnItem::find($addOn->add_on_item_id);

                if ($item) {
                    $addOn->item_name = $addOn->item_name ?: $item->name;
                    $addOn->unit_price = $addOn->unit_price ?: $item->unit_price;
                }
            }

            $addOn->total_amount = round((float) $addOn->unit_price * (float) $addOn->quantity, 2);
        });

        static::saved(function (GameAddOn $addOn): void {
            app(GameBillingService::class)->recalculate($addOn->gameSession);
        });

        static::deleted(function (GameAddOn $addOn): void {
            app(GameBillingService::class)->recalculate($addOn->gameSession);
        });
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    public function addOnItem(): BelongsTo
    {
        return $this->belongsTo(AddOnItem::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function getChargedPlayerLabelsAttribute(): string
    {
        $this->loadMissing(['player', 'gameSession.participants.player']);

        if ($this->charged_to === 'specific_player') {
            return $this->player?->name ?? 'Specific player not selected';
        }

        $chargedParticipants = $this->chargedParticipantsForDisplay();

        $labels = $chargedParticipants
            ->map(fn (GameParticipant $participant): string => $participant->player_label)
            ->filter()
            ->values()
            ->join(', ');

        if ($labels !== '') {
            return $labels;
        }

        return match ($this->charged_to) {
            'team_a' => 'Team A players not selected',
            'team_b' => 'Team B players not selected',
            'all_players' => 'Players not selected',
            default => 'Losers / payers not selected',
        };
    }

    public function getChargedPlayerPaymentStatusAttribute(): string
    {
        $chargedParticipants = $this->chargedParticipantsForDisplay();

        if ($chargedParticipants->isEmpty()) {
            return 'No charged players selected';
        }

        return $chargedParticipants
            ->map(fn (GameParticipant $participant): string => sprintf(
                '%s - %s - Balance %s',
                $participant->player_label,
                ucfirst($participant->payment_status),
                $this->money($participant->outstanding_amount),
            ))
            ->join('; ');
    }

    private function chargedParticipantsForDisplay()
    {
        $this->loadMissing(['player', 'gameSession.participants.player']);

        if ($this->charged_to === 'specific_player') {
            return $this->gameSession?->participants
                ?->where('player_id', $this->player_id)
                ->values() ?? collect();
        }

        $participants = $this->gameSession?->participants ?? collect();

        return match ($this->charged_to) {
            'team_a' => $participants->where('team', 'A')->values(),
            'team_b' => $participants->where('team', 'B')->values(),
            'all_players' => $participants->values(),
            default => $participants->where('is_loser', true)->values(),
        };
    }

    private function money(float|int|string|null $amount): string
    {
        return 'Rs ' . number_format((float) $amount, 2);
    }
}
