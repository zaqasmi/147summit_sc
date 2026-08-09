<?php

namespace App\Models;

use App\Services\TournamentProgressionService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TournamentMatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'tournament_id',
        'parent_match_id',
        'next_match_id',
        'next_match_slot',
        'round_number',
        'round_name',
        'match_number',
        'table_number',
        'player1_id',
        'player2_id',
        'winner_id',
        'match_format',
        'status',
        'player1_frames',
        'player2_frames',
        'player1_highest_break',
        'player2_highest_break',
        'scheduled_at',
        'started_at',
        'ended_at',
        'notes',
    ];

    protected $appends = [
        'required_frames_to_win',
        'score_label',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (TournamentMatch $match): void {
            if (! $match->winner_id) {
                if ((int) $match->player1_frames >= $match->required_frames_to_win && $match->player1_id) {
                    $match->winner_id = $match->player1_id;
                } elseif ((int) $match->player2_frames >= $match->required_frames_to_win && $match->player2_id) {
                    $match->winner_id = $match->player2_id;
                }
            }

            if ($match->winner_id && $match->status !== 'walkover') {
                $match->status = 'completed';
            }
        });

        static::saved(function (TournamentMatch $match): void {
            app(TournamentProgressionService::class)->advanceWinner($match);
        });
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    public function parentMatch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_match_id');
    }

    public function nextMatch(): BelongsTo
    {
        return $this->belongsTo(self::class, 'next_match_id');
    }

    public function player1(): BelongsTo
    {
        return $this->belongsTo(TournamentPlayer::class, 'player1_id');
    }

    public function player2(): BelongsTo
    {
        return $this->belongsTo(TournamentPlayer::class, 'player2_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(TournamentPlayer::class, 'winner_id');
    }

    public function frames(): HasMany
    {
        return $this->hasMany(TournamentMatchFrame::class);
    }

    public function getRequiredFramesToWinAttribute(): int
    {
        return match ($this->match_format) {
            'best_of_3' => 2,
            'best_of_7' => 4,
            'best_of_9' => 5,
            default => 3,
        };
    }

    public function getScoreLabelAttribute(): string
    {
        return ((int) $this->player1_frames).' - '.((int) $this->player2_frames);
    }

    public function isComplete(): bool
    {
        return $this->status === 'completed' && filled($this->winner_id);
    }

    public static function statusOptions(): array
    {
        return [
            'scheduled' => 'Scheduled',
            'ongoing' => 'Ongoing',
            'completed' => 'Completed',
            'walkover' => 'Walkover',
        ];
    }
}
