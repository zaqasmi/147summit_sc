<?php

namespace App\Filament\Support;

use App\Models\GameSession;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class CenturyTime
{
    public static function html(GameSession $session): HtmlString
    {
        if ($session->game_type !== 'century') {
            return new HtmlString('<span class="summit-century-muted">-</span>');
        }

        $status = $session->status === 'active' ? 'active' : 'ended';
        $label = self::label($session);
        $minutes = self::minutes($session);
        $subLabel = $status === 'active' ? 'running' : 'total';

        return new HtmlString(sprintf(
            '<div class="summit-century-timer" data-status="%s"><strong>%s</strong><span>%s</span><small>%s min</small></div>',
            e($status),
            e($label),
            e($subLabel),
            number_format($minutes),
        ));
    }

    public static function label(GameSession $session): string
    {
        $minutes = self::minutes($session);

        if ($minutes < 60) {
            return $minutes . 'm';
        }

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return $remainingMinutes > 0
            ? "{$hours}h {$remainingMinutes}m"
            : "{$hours}h";
    }

    public static function minutes(GameSession $session): int
    {
        if (! $session->started_at) {
            return 0;
        }

        $start = $session->started_at instanceof Carbon
            ? $session->started_at
            : Carbon::parse($session->started_at);

        $end = $session->ended_at instanceof Carbon
            ? $session->ended_at
            : ($session->ended_at ? Carbon::parse($session->ended_at) : now());

        return max(0, (int) $start->diffInMinutes($end));
    }
}
