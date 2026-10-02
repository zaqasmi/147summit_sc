<?php

namespace Tests\Feature;

use App\Models\BankTransaction;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ExistingBankMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_bank_rows_and_source_ids_are_preserved_during_the_live_upgrade(): void
    {
        $migration = require database_path('migrations/2026_10_02_000001_add_staff_payment_sources_and_bank_accounts.php');
        $migration->down();

        DB::table('expenses')->insert([
            'id' => 73, 'expense_date' => '2026-09-01', 'category' => 'Utilities',
            'description' => 'Existing power payment', 'amount' => 250, 'paid_from' => 'bank',
        ]);
        DB::table('bank_transactions')->insert([
            [
                'id' => 27, 'transaction_date' => '2026-09-01', 'type' => 'adjustment_in', 'amount' => 1000,
                'source_type' => 'opening_bank_balance', 'source_id' => 1,
                'description' => 'Existing opening balance', 'notes' => 'Live data',
                'created_at' => '2026-09-01 10:00:00', 'updated_at' => '2026-09-01 10:00:00',
            ],
            [
                'id' => 104, 'transaction_date' => '2026-09-01', 'type' => 'expense_paid', 'amount' => 250,
                'source_type' => 'expense', 'source_id' => 73,
                'description' => 'Existing power payment', 'notes' => null,
                'created_at' => '2026-09-01 10:00:00', 'updated_at' => '2026-09-01 10:00:00',
            ],
        ]);
        $before = DB::table('bank_transactions')->orderBy('id')->get()->map(fn ($row): array => (array) $row)->all();

        $migration->up();

        $after = DB::table('bank_transactions')->orderBy('id')->get()->map(function ($row): array {
            $this->assertSame('rf_account', $row->bank_account);
            $attributes = (array) $row;
            unset($attributes['bank_account']);

            return $attributes;
        })->all();
        $this->assertSame($before, $after);
        $this->assertSame('bank', Expense::findOrFail(73)->paid_from);
        $this->assertSame('RF Account', Expense::findOrFail(73)->paid_from_label);

        // Synchronizing an existing payment continues to update the same ledger ID.
        BankTransaction::syncFromExpense(Expense::findOrFail(73));
        $this->assertSame(104, Expense::findOrFail(73)->bankTransaction()->firstOrFail()->id);
        $this->assertSame(2, BankTransaction::count());
        $balances = BankTransaction::summary('2026-09-30');
        $this->assertSame(['rf_account' => 750.0, 'saving_account' => 0.0], $balances['account_balances']);
        $new = BankTransaction::create(['transaction_date' => '2026-09-02', 'type' => 'owner_deposit', 'amount' => 100, 'bank_account' => 'saving_account']);
        $this->assertGreaterThan(104, $new->id);
        $this->assertSame([27, 104], BankTransaction::where('bank_account', 'rf_account')->orderBy('id')->pluck('id')->all());
    }
}
