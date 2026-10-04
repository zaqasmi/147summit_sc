<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BankTransactions\BankTransactionResource;
use App\Models\BankTransaction;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class CashToDepositSummary extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected ?string $pollingInterval = '15s';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    #[On('cash-bank-balances-updated')]
    public function refreshBalances(): void {}

    protected function getStats(): array
    {
        abort_unless(static::canView(), 403);

        $summary = BankTransaction::summary();
        $money = fn ($value): string => 'Rs '.number_format((float) $value, 2);

        return [
            Stat::make('Cash to be deposited in bank', $money($summary['collection_cash_pending_deposit']))
                ->url(BankTransactionResource::getUrl())
                ->description('After collection payments and deposits to either account')
                ->color($summary['collection_cash_pending_deposit'] > 0 ? 'warning' : 'success'),
            Stat::make('RF Account', $money($summary['account_balances']['rf_account']))
                ->description('Balance as of today')->color('info'),
            Stat::make('Saving Account', $money($summary['account_balances']['saving_account']))
                ->description('Balance as of today')->color('info'),
        ];
    }
}
