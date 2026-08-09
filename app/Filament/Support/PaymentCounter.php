<?php

namespace App\Filament\Support;

use App\Models\GameParticipant;
use App\Models\GameSession;
use Illuminate\Support\HtmlString;

class PaymentCounter
{
    public static function sessionHtml(GameSession $session, int $selectedParticipantId = 0, ?float $selectedDiscount = null): HtmlString
    {
        $session->loadMissing(['participants.player', 'participants.payments']);

        $participants = $session->participants
            ->filter(fn (GameParticipant $participant): bool => ((float) $participant->base_amount > 0)
                || ((float) $participant->add_on_amount > 0)
                || ((float) $participant->total_due > 0)
                || ((float) $participant->amount_paid > 0))
            ->values();

        if ($participants->isEmpty()) {
            return new HtmlString(
                '<div class="summit-payment-split summit-payment-counter-empty">End the session first to calculate player amounts.</div>'
            );
        }

        $rows = $participants
            ->map(function (GameParticipant $participant) use ($selectedParticipantId, $selectedDiscount): string {
                $discount = ($participant->id === $selectedParticipantId && $selectedDiscount !== null)
                    ? $selectedDiscount
                    : (float) $participant->discount_amount;

                $subtotal = round((float) $participant->base_amount + (float) $participant->add_on_amount, 2);
                $discount = min($subtotal, max(0, round($discount, 2)));
                $due = max(0, round($subtotal - $discount, 2));
                $balance = max(0, round($due - (float) $participant->amount_paid, 2));

                return sprintf(
                    <<<'HTML'
<tr>
    <td>%s</td>
    <td class="summit-money">%s</td>
    <td class="summit-money">%s</td>
    <td class="summit-money">%s</td>
    <td class="summit-money">%s</td>
</tr>
HTML,
                    e($participant->player_label),
                    self::money($participant->base_amount),
                    self::money($participant->add_on_amount),
                    self::money($discount),
                    self::money($balance),
                );
            })
            ->join('');

        $note = $session->game_type === 'doubles'
            ? 'Each losing player pays full frame fee. Add-ons are split between the losing players.'
            : 'Frame/minute charges and add-ons are recorded separately for each player.';
        $centuryTiming = $session->game_type === 'century'
            ? self::centuryTimingHtml($session)
            : '';

        return new HtmlString(sprintf(
            <<<'HTML'
<div class="summit-payment-split">
    <div class="summit-payment-split-header">
        <strong>Player-wise amount</strong>
        <span>%s</span>
    </div>
    %s
    <div class="summit-payment-split-table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Player</th>
                    <th>Frames/minutes</th>
                    <th>Add-ons</th>
                    <th>Discount</th>
                    <th>Balance</th>
                </tr>
            </thead>
            <tbody>%s</tbody>
        </table>
    </div>
</div>
HTML,
            e($note),
            $centuryTiming,
            $rows,
        ));
    }

    public static function html(?GameParticipant $participant, float $discount = 0): HtmlString
    {
        if (! $participant) {
            return new HtmlString(
                '<div class="summit-payment-counter summit-payment-counter-empty">Select a player to see the counter total.</div>'
            );
        }

        $participant->loadMissing(['gameSession', 'player']);

        $baseAmount = (float) $participant->base_amount;
        $addOnAmount = (float) $participant->add_on_amount;
        $subtotal = round($baseAmount + $addOnAmount, 2);
        $discount = min($subtotal, max(0, round($discount, 2)));
        $alreadyPaid = (float) $participant->amount_paid;
        $totalDue = max(0, round($subtotal - $discount, 2));
        $collectNow = max(0, round($totalDue - $alreadyPaid, 2));

        $session = $participant->gameSession;
        $baseLabel = $session?->isFrameGame() ? 'Frame charge' : 'Minute charge';
        $baseMeta = $session?->isFrameGame()
            ? number_format((int) $session->frames_played) . ' frames x ' . self::money($session->frame_fee)
            : number_format((int) ($session?->duration_minutes ?? 0)) . ' minutes x ' . self::money($session?->hourly_rate ?? 0);

        return new HtmlString(sprintf(
            <<<'HTML'
<div class="summit-payment-counter">
    <div class="summit-payment-counter-header">
        <div>
            <div class="summit-payment-counter-label">Counter total</div>
            <div class="summit-payment-counter-player">%s</div>
        </div>
        <div class="summit-payment-counter-total">
            <span>Collect now</span>
            <strong>%s</strong>
        </div>
    </div>
    <div class="summit-payment-counter-grid">
        <div class="summit-payment-counter-tile">
            <span>%s</span>
            <strong>%s</strong>
            <small>%s</small>
        </div>
        <div class="summit-payment-counter-tile">
            <span>Add-ons</span>
            <strong>%s</strong>
            <small>Tea, drinks, snacks</small>
        </div>
        <div class="summit-payment-counter-tile">
            <span>Subtotal</span>
            <strong>%s</strong>
            <small>Frames plus add-ons</small>
        </div>
        <div class="summit-payment-counter-tile summit-payment-counter-discount">
            <span>Discount</span>
            <strong>%s</strong>
            <small>Applied before cash</small>
        </div>
        <div class="summit-payment-counter-tile summit-payment-counter-paid">
            <span>Already paid</span>
            <strong>%s</strong>
            <small>Previous receipts</small>
        </div>
    </div>
</div>
HTML,
            e($participant->player_label),
            self::money($collectNow),
            e($baseLabel),
            self::money($baseAmount),
            e($baseMeta),
            self::money($addOnAmount),
            self::money($subtotal),
            self::money($discount),
            self::money($alreadyPaid),
        ));
    }

    public static function balanceAfterDiscount(GameParticipant $participant, float $discount): float
    {
        $grossTotal = (float) $participant->base_amount + (float) $participant->add_on_amount;
        $totalDue = max(0, round($grossTotal - max(0, $discount), 2));

        return max(0, round($totalDue - (float) $participant->amount_paid, 2));
    }

    public static function money(float|int|string|null $amount): string
    {
        return 'Rs ' . number_format((float) $amount, 2);
    }

    private static function centuryTimingHtml(GameSession $session): string
    {
        $session->loadMissing('snookerTable');

        $start = $session->started_at?->format('d M Y h:i A') ?? '-';
        $end = $session->ended_at?->format('d M Y h:i A') ?? 'Running';
        $minutes = CenturyTime::minutes($session);

        return sprintf(
            <<<'HTML'
<div class="summit-century-payment-time">
    <div>
        <span>Start time</span>
        <strong>%s</strong>
    </div>
    <div>
        <span>End time</span>
        <strong>%s</strong>
    </div>
    <div>
        <span>Total minutes</span>
        <strong>%s min</strong>
    </div>
</div>
HTML,
            e($start),
            e($end),
            number_format($minutes),
        );
    }
}
