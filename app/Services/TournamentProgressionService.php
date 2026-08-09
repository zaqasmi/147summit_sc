<?php

namespace App\Services;

use App\Models\TournamentMatch;

class TournamentProgressionService
{
    public function syncScores(TournamentMatch $match): void
    {
        $match->loadMissing('frames');

        $player1Frames = 0;
        $player2Frames = 0;
        $player1HighestBreak = 0;
        $player2HighestBreak = 0;

        foreach ($match->frames as $frame) {
            $player1HighestBreak = max($player1HighestBreak, (int) $frame->player1_highest_break);
            $player2HighestBreak = max($player2HighestBreak, (int) $frame->player2_highest_break);

            if ((int) $frame->winner_id === (int) $match->player1_id) {
                $player1Frames++;
            } elseif ((int) $frame->winner_id === (int) $match->player2_id) {
                $player2Frames++;
            } elseif ((int) $frame->player1_score !== (int) $frame->player2_score) {
                (int) $frame->player1_score > (int) $frame->player2_score
                    ? $player1Frames++
                    : $player2Frames++;
            }
        }

        $winnerId = $match->winner_id;
        $status = $match->status;

        if ($player1Frames >= $match->required_frames_to_win && $match->player1_id) {
            $winnerId = $match->player1_id;
            $status = 'completed';
        } elseif ($player2Frames >= $match->required_frames_to_win && $match->player2_id) {
            $winnerId = $match->player2_id;
            $status = 'completed';
        }

        $match->forceFill([
            'player1_frames' => $player1Frames,
            'player2_frames' => $player2Frames,
            'player1_highest_break' => $player1HighestBreak,
            'player2_highest_break' => $player2HighestBreak,
            'winner_id' => $winnerId,
            'status' => $status,
        ])->saveQuietly();

        $this->advanceWinner($match->refresh());
    }

    public function advanceWinner(TournamentMatch $match): void
    {
        if (! $match->isComplete() || ! $match->next_match_id || ! $match->next_match_slot) {
            return;
        }

        $column = $match->next_match_slot === 'player2' ? 'player2_id' : 'player1_id';
        $nextMatch = $match->nextMatch;

        if (! $nextMatch || (int) $nextMatch->{$column} === (int) $match->winner_id) {
            return;
        }

        $nextMatch->forceFill([
            $column => $match->winner_id,
            'status' => $nextMatch->status === 'completed' ? 'completed' : 'scheduled',
        ])->saveQuietly();
    }
}
