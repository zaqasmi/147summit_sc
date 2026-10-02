<?php

namespace App\Filament\Concerns;

use App\Filament\Widgets\CashToDepositSummary;
use Filament\Actions\Action;

trait ShowsCashBankBalances
{
    protected function getHeaderWidgets(): array
    {
        return [CashToDepositSummary::class, ...parent::getHeaderWidgets()];
    }

    protected function afterActionCalled(Action $action): void
    {
        parent::afterActionCalled($action);
        $this->dispatch('cash-bank-balances-updated');
    }
}
