<?php

namespace App\Models;

use App\Services\TournamentProgressionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatchFrame extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_match_id',
        'frame_number',
        'player1_score',
        'player2_score',
        'winner_id',
        'player1_highest_break',
        'player2_highest_break',
        'notes',
    ];

    protected static function booted(): void
    {
        static::saved(function (TournamentMatchFrame $frame): void {
            if ($frame->match) {
                app(TournamentProgressionService::class)->syncScores($frame->match);
            }
        });

        static::deleted(function (TournamentMatchFrame $frame): void {
            if ($frame->match) {
                app(TournamentProgressionService::class)->syncScores($frame->match);
            }
        });
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(TournamentMatch::class, 'tournament_match_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(TournamentPlayer::class, 'winner_id');
    }
}
