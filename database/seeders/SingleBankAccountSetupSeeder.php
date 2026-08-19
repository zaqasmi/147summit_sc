<?php

namespace Database\Seeders;

use App\Models\BankTransaction;
use Illuminate\Database\Seeder;

class SingleBankAccountSetupSeeder extends Seeder
{
    private const TARGET_BANK_BALANCE = 610000.0;

    private const TARGET_PENDING_CASH = 26000.0;

    private const SETUP_DATE = '2026-08-19';

    public function run(): void
    {
        BankTransaction::deleteForSource(BankTransaction::SOURCE_OPENING_BANK_BALANCE, 1);
        BankTransaction::deleteForSource(BankTransaction::SOURCE_OPENING_PENDING_CASH, 1);

        $summary = BankTransaction::summary(self::SETUP_DATE);

        $this->reconcileBankBalance((float) $summary['cash_in_bank']);
        $this->reconcilePendingCash((float) $summary['collection_cash_pending_deposit']);
    }

    private function reconcileBankBalance(float $currentBalance): void
    {
        $difference = round(self::TARGET_BANK_BALANCE - $currentBalance, 2);

        if ($difference === 0.0) {
            return;
        }

        BankTransaction::query()->create([
            'transaction_date' => self::SETUP_DATE,
            'type' => $difference > 0 ? 'adjustment_in' : 'adjustment_out',
            'amount' => abs($difference),
            'source_type' => BankTransaction::SOURCE_OPENING_BANK_BALANCE,
            'source_id' => 1,
            'description' => 'Single bank account opening balance reconciliation',
            'notes' => 'Sets current bank balance to Rs '.number_format(self::TARGET_BANK_BALANCE, 2).'.',
        ]);
    }

    private function reconcilePendingCash(float $currentPendingCash): void
    {
        $difference = round(self::TARGET_PENDING_CASH - $currentPendingCash, 2);

        if ($difference === 0.0) {
            return;
        }

        BankTransaction::query()->create([
            'transaction_date' => self::SETUP_DATE,
            'type' => $difference > 0 ? 'cash_pending_adjustment_in' : 'cash_pending_adjustment_out',
            'amount' => abs($difference),
            'source_type' => BankTransaction::SOURCE_OPENING_PENDING_CASH,
            'source_id' => 1,
            'description' => 'Opening cash pending bank reconciliation',
            'notes' => 'Sets cash still to be deposited in bank to Rs '.number_format(self::TARGET_PENDING_CASH, 2).'.',
        ]);
    }
}
