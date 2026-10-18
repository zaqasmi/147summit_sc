<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentDrawSetting;
use App\Models\TournamentMatch;
use App\Models\TournamentPlayer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TournamentDrawService
{
    public function generateKnockout(Tournament $tournament): void
    {
        $players = $tournament->players()
            ->orderByRaw('seed is null')
            ->orderBy('seed')
            ->orderBy('full_name')
            ->get();

        if ($players->count() < 2) {
            throw ValidationException::withMessages([
                'players' => 'At least two registered players are required to generate a draw.',
            ]);
        }

        if ($tournament->matches()->whereNotNull('winner_id')->exists()) {
            throw ValidationException::withMessages([
                'matches' => 'This tournament already has match results. Clear results before regenerating the draw.',
            ]);
        }

        $bracketSize = $this->nextPowerOfTwo($players->count());
        $selectedIds = TournamentDrawSetting::query()->where('tournament_id', $tournament->id)->first()?->separated_player_ids ?? [];
        $selectedIds = $players->whereIn('id', $selectedIds)->pluck('id')->all();
        if (count($selectedIds) > $bracketSize / 2) {
            throw ValidationException::withMessages([
                'players' => 'The selected players cannot all be kept apart. Select at most '.($bracketSize / 2).' players for this first-round bracket, or register more players.',
            ]);
        }
        $slots = $this->seedSlots($players, $bracketSize);
        if ($selectedIds !== []) {
            $slots = $this->separateSelectedPlayers($slots, $selectedIds);
        }

        DB::transaction(function () use ($tournament, $bracketSize, $slots): void {
            $tournament->matches()->delete();

            $totalRounds = (int) log($bracketSize, 2);
            $roundMatches = [];

            for ($round = 1; $round <= $totalRounds; $round++) {
                $matchesInRound = (int) ($bracketSize / (2 ** $round));
                $roundMatches[$round] = [];

                for ($index = 0; $index < $matchesInRound; $index++) {
                    $roundMatches[$round][$index] = TournamentMatch::create([
                        'tournament_id' => $tournament->id,
                        'round_number' => $round,
                        'round_name' => $this->roundName($round, $totalRounds, $matchesInRound),
                        'match_number' => $index + 1,
                        'player1_id' => $round === 1 ? ($slots[$index * 2]?->id) : null,
                        'player2_id' => $round === 1 ? ($slots[$index * 2 + 1]?->id) : null,
                        'match_format' => $tournament->match_format,
                        'status' => 'scheduled',
                    ]);
                }
            }

            for ($round = 1; $round < $totalRounds; $round++) {
                foreach ($roundMatches[$round] as $index => $match) {
                    $nextMatch = $roundMatches[$round + 1][(int) floor($index / 2)];
                    $match->updateQuietly([
                        'next_match_id' => $nextMatch->id,
                        'next_match_slot' => $index % 2 === 0 ? 'player1' : 'player2',
                    ]);
                }
            }

            foreach ($roundMatches[1] as $match) {
                $winnerId = null;

                if ($match->player1_id && ! $match->player2_id) {
                    $winnerId = $match->player1_id;
                } elseif ($match->player2_id && ! $match->player1_id) {
                    $winnerId = $match->player2_id;
                }

                if ($winnerId) {
                    $match->updateQuietly([
                        'winner_id' => $winnerId,
                        'status' => 'completed',
                        'notes' => 'BYE',
                    ]);

                    app(TournamentProgressionService::class)->advanceWinner($match->refresh());
                }
            }

            $tournament->update([
                'draw_generated_at' => now(),
                'status' => 'ongoing',
            ]);
        });
    }

    /**
     * @param  array<int, TournamentPlayer|null>  $slots
     * @param  array<int, int>  $selectedIds
     * @return array<int, TournamentPlayer|null>
     */
    private function separateSelectedPlayers(array $slots, array $selectedIds): array
    {
        // Fill empty matches first so every bye advances into a playable bracket.
        for ($index = 0; $index < count($slots); $index += 2) {
            if ($slots[$index] || $slots[$index + 1]) {
                continue;
            }
            for ($donor = 0; $donor < count($slots); $donor += 2) {
                if ($slots[$donor] && $slots[$donor + 1]) {
                    $slots[$index] = $slots[$donor + 1];
                    $slots[$donor + 1] = null;
                    break;
                }
            }
        }

        $selected = fn (?TournamentPlayer $player): bool => $player && in_array($player->id, $selectedIds, true);
        for ($index = 0; $index < count($slots); $index += 2) {
            if (! $selected($slots[$index]) || ! $selected($slots[$index + 1])) {
                continue;
            }
            for ($other = 0; $other < count($slots); $other += 2) {
                if (! $selected($slots[$other]) && ! $selected($slots[$other + 1])) {
                    [$slots[$index + 1], $slots[$other]] = [$slots[$other], $slots[$index + 1]];
                    break;
                }
            }
        }

        return $slots;
    }

    /**
     * @param  Collection<int, TournamentPlayer>  $players
     * @return array<int, TournamentPlayer|null>
     */
    private function seedSlots(Collection $players, int $bracketSize): array
    {
        $slots = array_fill(0, $bracketSize, null);
        $seedPositions = $this->seedPositions($bracketSize);
        $seeded = $players->filter(fn (TournamentPlayer $player): bool => filled($player->seed));
        $unseeded = $this->balanceAvoidGroups($players->reject(fn (TournamentPlayer $player): bool => filled($player->seed))->values());

        foreach ($seeded as $player) {
            $position = $seedPositions[max(1, (int) $player->seed)] ?? null;

            if ($position !== null && $position < $bracketSize && $slots[$position] === null) {
                $slots[$position] = $player;
            } else {
                $unseeded->push($player);
            }
        }

        foreach ($unseeded as $player) {
            $emptyIndex = array_search(null, $slots, true);

            if ($emptyIndex === false) {
                break;
            }

            $slots[$emptyIndex] = $player;
        }

        return $slots;
    }

    /**
     * @param  Collection<int, TournamentPlayer>  $players
     * @return Collection<int, TournamentPlayer>
     */
    private function balanceAvoidGroups(Collection $players): Collection
    {
        if ($players->count() < 4) {
            return $players->shuffle()->values();
        }

        $best = $players->shuffle()->values();
        $bestScore = $this->avoidGroupScore($best);

        for ($attempt = 0; $attempt < 80; $attempt++) {
            $candidate = $players->shuffle()->values();
            $score = $this->avoidGroupScore($candidate);

            if ($score < $bestScore) {
                $best = $candidate;
                $bestScore = $score;
            }

            if ($bestScore === 0) {
                break;
            }
        }

        return $best;
    }

    /**
     * @param  Collection<int, TournamentPlayer>  $players
     */
    private function avoidGroupScore(Collection $players): int
    {
        $score = 0;

        for ($index = 0; $index < $players->count(); $index += 2) {
            $first = $players->get($index);
            $second = $players->get($index + 1);

            if ($first && $second && filled($first->avoid_group) && $first->avoid_group === $second->avoid_group) {
                $score++;
            }
        }

        return $score;
    }

    /**
     * @return array<int, int>
     */
    private function seedPositions(int $bracketSize): array
    {
        $positions = [];

        for ($seed = 1; $seed <= $bracketSize; $seed++) {
            $positions[$seed] = ($seed - 1) % 2 === 0
                ? (int) floor(($seed - 1) / 2)
                : $bracketSize - 1 - (int) floor(($seed - 1) / 2);
        }

        return $positions;
    }

    private function nextPowerOfTwo(int $number): int
    {
        $power = 1;

        while ($power < $number) {
            $power *= 2;
        }

        return $power;
    }

    private function roundName(int $round, int $totalRounds, int $matchesInRound): string
    {
        return match (true) {
            $round === $totalRounds => 'Final',
            $round === $totalRounds - 1 => 'Semi Final',
            $round === $totalRounds - 2 => 'Quarter Final',
            $matchesInRound === 16 => 'Round of 32',
            $matchesInRound === 8 => 'Round of 16',
            default => 'Round '.$round,
        };
    }
}
