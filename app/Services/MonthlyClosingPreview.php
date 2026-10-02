<?php

namespace App\Services;

use Illuminate\Support\Carbon;

class MonthlyClosingPreview
{
    private array $reports = [];

    public function report(Carbon|string $month, array $override = []): array
    {
        $month = Carbon::parse($month)->startOfMonth()->toDateString();
        $key = hash('sha256', serialize([$month, $override]));

        return $this->reports[$key] ??= app(ReportService::class)->monthly($month, $override ?: null);
    }

    public function clear(): void
    {
        $this->reports = [];
    }
}
