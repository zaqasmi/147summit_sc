<?php

namespace App\Filament\Widgets;

use App\Models\GameParticipant;
use App\Models\GameSession;
use App\Services\ReportService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class SaleManagerOverview extends Widget
{
    use InteractsWithPageFilters;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = 1;

    protected string $view = 'filament.widgets.sale-manager-overview';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $selectedDate = $this->selectedDate();
        $report = app(ReportService::class)->daily($selectedDate, withCapital: false);
        $activeSessions = GameSession::query()->active()->count();
        $openBalances = max(
            0,
            (float) GameParticipant::query()->outstanding()->sum('total_due')
                - (float) GameParticipant::query()->outstanding()->sum('amount_paid'),
        );

        return [
            'asOfLabel' => $selectedDate->format('d M Y'),
            'heroStats' => [
                $this->stat('Total collection', $report['total_collection'], 'Actual cash collected after customer dues.', 'heroicon-o-banknotes', 'success'),
                $this->stat('Gross sale', $report['gross_sales_total'], 'Full table sale before unpaid customer dues.', 'heroicon-o-chart-bar', 'info'),
                $this->stat('Salesmen commission', $report['staff_share_estimate'], 'Commission calculated from actual cash profit.', 'heroicon-o-receipt-percent', 'warning'),
                $this->stat('Payment due', $openBalances, 'Open balances still pending from players.', 'heroicon-o-exclamation-triangle', $openBalances > 0 ? 'danger' : 'success'),
            ],
            'sections' => [
                [
                    'title' => 'Cash movement',
                    'description' => 'Use these numbers to understand what happened in today\'s closing.',
                    'stats' => [
                        $this->stat('Expenses', $report['expense_total'], 'Operating cost deducted from sale.', 'heroicon-o-receipt-refund', ((float) $report['expense_total']) > 0 ? 'danger' : 'gray'),
                        $this->stat('Customer dues', $report['dues_added'], 'Sale kept as customer due, not cash collected.', 'heroicon-o-user-minus', ((float) $report['dues_added']) > 0 ? 'danger' : 'success'),
                        $this->stat('Advance paid', $report['staff_paid_total'], 'Advance already paid to staff.', 'heroicon-o-wallet', ((float) $report['staff_paid_total']) > 0 ? 'warning' : 'gray'),
                        $this->stat('Add-ons', $report['add_on_total'], 'Items recorded separately from table sale.', 'heroicon-o-squares-plus', 'info'),
                    ],
                ],
                [
                    'title' => 'Floor status',
                    'description' => 'Fast read of current activity and frame volume.',
                    'stats' => [
                        $this->stat('Active tables', $activeSessions, 'Sessions currently running.', 'heroicon-o-play-circle', $activeSessions > 0 ? 'warning' : 'success', asMoney: false),
                        $this->stat('Frames', (int) $report['frames_count'], 'Solo and doubles frames on selected date.', 'heroicon-o-table-cells', 'info', asMoney: false),
                        $this->stat('Sessions', (int) $report['sessions_count'], 'Checked-out sessions for selected date.', 'heroicon-o-queue-list', 'gray', asMoney: false),
                        $this->stat('Cash source', $report['source_label'], 'Where this dashboard data is coming from.', 'heroicon-o-document-check', 'gray', isText: true),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{label: string, value: string, description: string, icon: string, color: string}
     */
    private function stat(
        string $label,
        float|int|string|null $value,
        string $description,
        string $icon,
        string $color,
        bool $asMoney = true,
        bool $isText = false,
    ): array {
        return [
            'label' => $label,
            'value' => $isText ? (string) $value : ($asMoney ? $this->money($value) : number_format((float) $value)),
            'description' => $description,
            'icon' => $icon,
            'color' => $color,
        ];
    }

    private function selectedDate(): Carbon
    {
        $date = $this->pageFilters['date'] ?? null;

        try {
            return Carbon::parse($date ?: today())->startOfDay();
        } catch (\Throwable) {
            return today();
        }
    }

    private function money(float|int|string|null $amount): string
    {
        return 'Rs '.number_format((float) $amount, 2);
    }
}
