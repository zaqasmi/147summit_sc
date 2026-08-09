<?php

namespace App\Services;

use App\Models\GameAddOn;
use App\Models\GameParticipant;
use App\Models\GameSession;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class GameBillingService
{
    public function recalculate(?GameSession $session): void
    {
        if (! $session || ! $session->exists) {
            return;
        }

        DB::transaction(function () use ($session): void {
            $session->load(['participants.payments', 'addOns']);

            $participants = $session->participants;

            if ($participants->isEmpty()) {
                $session->forceFill(['discount_total' => 0])->saveQuietly();

                return;
            }

            $amounts = [];

            foreach ($participants as $participant) {
                $amounts[$participant->id] = [
                    'base_amount' => 0.0,
                    'add_on_amount' => 0.0,
                ];
            }

            if ($session->isFrameGame()) {
                $frameCharge = max(0, (int) $session->frames_played) * (float) $session->frame_fee;

                foreach ($participants->where('is_loser', true) as $participant) {
                    $amounts[$participant->id]['base_amount'] += $frameCharge;
                }
            } else {
                $total = round($session->duration_minutes * (float) $session->hourly_rate, 2);
                $receivers = $this->primaryChargeParticipants($participants);
                $this->splitAmount($amounts, $receivers, $total, 'base_amount');
            }

            foreach ($session->addOns as $addOn) {
                $receivers = $this->participantsForAddOn($participants, $addOn);
                $this->splitAmount($amounts, $receivers, (float) $addOn->total_amount, 'add_on_amount');
            }

            $discountTotal = 0;

            foreach ($participants as $participant) {
                $baseAmount = round($amounts[$participant->id]['base_amount'], 2);
                $addOnAmount = round($amounts[$participant->id]['add_on_amount'], 2);
                $discountAmount = round((float) $participant->discount_amount, 2);
                $totalDue = max(0, round($baseAmount + $addOnAmount - $discountAmount, 2));
                $amountPaid = round((float) $participant->payments->sum('amount'), 2);

                $participant->forceFill([
                    'base_amount' => $baseAmount,
                    'add_on_amount' => $addOnAmount,
                    'total_due' => $totalDue,
                    'amount_paid' => $amountPaid,
                    'payment_status' => $this->paymentStatus($totalDue, $amountPaid),
                ])->saveQuietly();

                $discountTotal += $discountAmount;
            }

            $session->forceFill([
                'discount_total' => round($discountTotal, 2),
            ])->saveQuietly();
        });
    }

    /**
     * @param  Collection<int, GameParticipant>  $participants
     * @return Collection<int, GameParticipant>
     */
    private function primaryChargeParticipants(Collection $participants): Collection
    {
        $losers = $participants->where('is_loser', true);

        return $losers->isNotEmpty() ? $losers->values() : $participants->values();
    }

    /**
     * @param  Collection<int, GameParticipant>  $participants
     * @return Collection<int, GameParticipant>
     */
    private function participantsForAddOn(Collection $participants, GameAddOn $addOn): Collection
    {
        $receivers = match ($addOn->charged_to) {
            'team_a' => $participants->where('team', 'A'),
            'team_b' => $participants->where('team', 'B'),
            'specific_player' => $participants->where('player_id', $addOn->player_id),
            'all_players' => $participants,
            default => $this->primaryChargeParticipants($participants),
        };

        return $receivers->isNotEmpty() ? $receivers->values() : $participants->values();
    }

    /**
     * @param  array<int, array{base_amount: float, add_on_amount: float}>  $amounts
     * @param  Collection<int, GameParticipant>  $participants
     */
    private function splitAmount(array &$amounts, Collection $participants, float $amount, string $column): void
    {
        if ($participants->isEmpty() || $amount <= 0) {
            return;
        }

        $share = round($amount / $participants->count(), 2);
        $allocated = 0.0;
        $lastParticipant = $participants->last();

        foreach ($participants as $participant) {
            $lineShare = $participant->is($lastParticipant)
                ? round($amount - $allocated, 2)
                : $share;

            $amounts[$participant->id][$column] += $lineShare;
            $allocated += $lineShare;
        }
    }

    private function paymentStatus(float $totalDue, float $amountPaid): string
    {
        if ($totalDue <= 0 || $amountPaid >= $totalDue) {
            return 'paid';
        }

        if ($amountPaid > 0) {
            return 'partial';
        }

        return 'unpaid';
    }
}
