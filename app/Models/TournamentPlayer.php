<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentPlayer extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'player_id',
        'full_name',
        'father_name',
        'photo_path',
        'club_name',
        'district',
        'contact_number',
        'cnic',
        'registration_number',
        'registration_fee',
        'fee_status',
        'payment_date',
        'seed',
        'avoid_group',
        'ranking_points',
        'remarks',
    ];

    protected $appends = [
        'matches_played',
        'matches_won',
        'highest_break',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'registration_fee' => 'decimal:2',
            'ranking_points' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TournamentPlayer $player): void {
            if (blank($player->full_name) && $player->player_id) {
                $player->full_name = Player::query()->find($player->player_id)?->name;
            }

            if ((float) $player->registration_fee <= 0 && $player->tournament_id) {
                $player->registration_fee = Tournament::query()->find($player->tournament_id)?->registration_fee ?? 0;
            }

            if (blank($player->registration_number) && $player->tournament_id) {
                $nextNumber = static::query()
                    ->where('tournament_id', $player->tournament_id)
                    ->count() + 1;

                $player->registration_number = 'T'.$player->tournament_id.'-'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(Player::class);
    }

    public function playerOneMatches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'player1_id');
    }

    public function playerTwoMatches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'player2_id');
    }

    public function wonMatches(): HasMany
    {
        return $this->hasMany(TournamentMatch::class, 'winner_id');
    }

    public function getMatchesPlayedAttribute(): int
    {
        return TournamentMatch::query()
            ->where('tournament_id', $this->tournament_id)
            ->where('status', 'completed')
            ->where(function ($query): void {
                $query->where('player1_id', $this->id)
                    ->orWhere('player2_id', $this->id);
            })
            ->count();
    }

    public function getMatchesWonAttribute(): int
    {
        return $this->wonMatches()->where('status', 'completed')->count();
    }

    public function getHighestBreakAttribute(): int
    {
        return max(
            (int) TournamentMatch::query()->where('player1_id', $this->id)->max('player1_highest_break'),
            (int) TournamentMatch::query()->where('player2_id', $this->id)->max('player2_highest_break'),
            (int) TournamentMatchFrame::query()
                ->whereHas('match', fn ($query) => $query->where('player1_id', $this->id))
                ->max('player1_highest_break'),
            (int) TournamentMatchFrame::query()
                ->whereHas('match', fn ($query) => $query->where('player2_id', $this->id))
                ->max('player2_highest_break'),
        );
    }

    public static function feeStatusOptions(): array
    {
        return [
            'paid' => 'Paid',
            'unpaid' => 'Unpaid',
        ];
    }
}
