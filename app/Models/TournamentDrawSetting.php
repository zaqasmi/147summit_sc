<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class TournamentDrawSetting extends Model
{
    protected $fillable = ['tournament_id', 'separated_player_ids'];

    protected function casts(): array
    {
        return ['separated_player_ids' => 'array'];
    }

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }

    protected static function booted(): void
    {
        static::saving(function (TournamentDrawSetting $setting): void {
            $ids = array_values(array_unique(array_map('intval', $setting->separated_player_ids ?? [])));
            $tournament = $setting->tournament()->first();
            if (! $tournament || $tournament->type !== 'knockout') {
                throw ValidationException::withMessages(['tournament_id' => 'Select a knockout tournament.']);
            }
            if ($tournament->players()->whereIn('id', $ids)->count() !== count($ids)) {
                throw ValidationException::withMessages(['separated_player_ids' => 'Select only players registered in this tournament.']);
            }
            $setting->separated_player_ids = $ids;
        });
    }
}
