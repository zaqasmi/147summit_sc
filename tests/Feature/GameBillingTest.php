<?php

namespace Tests\Feature;

use App\Filament\Pages\DailyReport;
use App\Filament\Resources\Expenses\ExpenseResource;
use App\Filament\Resources\GameAddOns\GameAddOnResource;
use App\Filament\Resources\GameParticipants\GameParticipantResource;
use App\Filament\Resources\GameSessions\GameSessionResource;
use App\Filament\Resources\Players\PlayerResource;
use App\Filament\Support\CenturyTime;
use App\Models\BankTransaction;
use App\Models\CapitalLiability;
use App\Models\CapitalLiabilityPayment;
use App\Models\CashDeposit;
use App\Models\CommissionRate;
use App\Models\CustomerDue;
use App\Models\CustomerDueCharge;
use App\Models\CustomerDuePayment;
use App\Models\Expense;
use App\Models\GameAddOn;
use App\Models\GameParticipant;
use App\Models\GameSession;
use App\Models\MonthlyClosing;
use App\Models\MonthlyCommission;
use App\Models\OwnerCapital;
use App\Models\Payment;
use App\Models\Player;
use App\Models\SnookerTable;
use App\Models\Staff;
use App\Models\StaffTransaction;
use App\Models\User;
use App\Services\CustomerDuePdfReport;
use App\Services\ReportService;
use App\Support\StaffTransactionCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GameBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_cannot_have_two_active_sessions(): void
    {
        $table = SnookerTable::create([
            'number' => 1,
            'name' => 'Table 1',
            'hourly_rate' => 10,
        ]);

        GameSession::create([
            'snooker_table_id' => $table->id,
            'game_type' => 'one_to_one',
            'status' => 'active',
            'started_at' => Carbon::parse('2026-07-29 18:00:00'),
            'frames_played' => 0,
            'frame_fee' => 100,
            'hourly_rate' => 10,
        ]);

        $this->expectException(ValidationException::class);

        GameSession::create([
            'snooker_table_id' => $table->id,
            'game_type' => 'doubles',
            'status' => 'active',
            'started_at' => Carbon::parse('2026-07-29 18:30:00'),
            'frames_played' => 0,
            'frame_fee' => 100,
            'hourly_rate' => 10,
        ]);
    }

    public function test_solo_frame_game_uses_only_frame_fee_for_the_loser(): void
    {
        $table = SnookerTable::create([
            'number' => 1,
            'name' => 'Table 1',
            'hourly_rate' => 10,
        ]);

        $session = GameSession::create([
            'snooker_table_id' => $table->id,
            'game_type' => 'one_to_one',
            'status' => 'active',
            'started_at' => Carbon::parse('2026-07-29 18:00:00'),
            'ended_at' => Carbon::parse('2026-07-29 20:00:00'),
            'frames_played' => 0,
            'frame_fee' => 100,
            'hourly_rate' => 10,
        ]);

        $winner = $session->participants()->create([
            'player_name_snapshot' => 'Winner',
            'team' => 'solo',
        ]);

        $loser = $session->participants()->create([
            'player_name_snapshot' => 'Loser',
            'team' => 'solo',
            'is_loser' => true,
        ]);

        $this->assertSame('0.00', $loser->refresh()->total_due);

        $session->update(['frames_played' => 2]);

        $this->assertSame('0.00', $winner->refresh()->total_due);
        $this->assertSame('200.00', $loser->refresh()->total_due);
    }

    public function test_doubles_losing_team_each_pays_frames_and_split_addons(): void
    {
        $table = SnookerTable::create([
            'number' => 1,
            'name' => 'Table 1',
            'hourly_rate' => 10,
        ]);

        $players = collect(['A One', 'A Two', 'B One', 'B Two'])
            ->map(fn (string $name): Player => Player::create(['name' => $name]));

        $session = GameSession::create([
            'snooker_table_id' => $table->id,
            'game_type' => 'doubles',
            'status' => 'checked_out',
            'started_at' => Carbon::parse('2026-07-29 18:00:00'),
            'ended_at' => Carbon::parse('2026-07-29 19:00:00'),
            'checked_out_at' => Carbon::parse('2026-07-29 19:05:00'),
            'frames_played' => 3,
            'frame_fee' => 100,
            'hourly_rate' => 10,
        ]);

        $session->participants()->create([
            'player_id' => $players[0]->id,
            'team' => 'A',
        ]);
        $session->participants()->create([
            'player_id' => $players[1]->id,
            'team' => 'A',
        ]);
        $loserOne = $session->participants()->create([
            'player_id' => $players[2]->id,
            'team' => 'B',
            'is_loser' => true,
        ]);
        $loserTwo = $session->participants()->create([
            'player_id' => $players[3]->id,
            'team' => 'B',
            'is_loser' => true,
        ]);

        $addOn = GameAddOn::create([
            'game_session_id' => $session->id,
            'item_name' => 'Tea',
            'unit_price' => 100,
            'quantity' => 2,
            'charged_to' => 'losers',
        ]);

        $loserOne->refresh();
        $loserTwo->refresh();

        $this->assertSame('300.00', $loserOne->base_amount);
        $this->assertSame('100.00', $loserOne->add_on_amount);
        $this->assertSame('400.00', $loserOne->total_due);
        $this->assertSame('400.00', $loserTwo->total_due);
        $this->assertSame('B One, B Two', $addOn->charged_player_labels);
        $this->assertSame(800.0, $session->refresh()->grand_total);

        $loserOne->payments()->create([
            'payment_date' => '2026-07-29',
            'payment_method' => 'cash',
            'amount' => 250,
        ]);

        $loserOne->refresh();

        $this->assertSame('250.00', $loserOne->amount_paid);
        $this->assertSame('partial', $loserOne->payment_status);
        $this->assertSame(150.0, $loserOne->outstanding_amount);

        $loserOne->payments()->create([
            'payment_date' => '2026-07-29',
            'payment_method' => 'cash',
            'amount' => 150,
        ]);

        $loserOne->refresh();
        $loserTwo->refresh();

        $this->assertSame('paid', $loserOne->payment_status);
        $this->assertSame(0.0, $loserOne->outstanding_amount);
        $this->assertSame('unpaid', $loserTwo->payment_status);
        $this->assertSame(400.0, $loserTwo->outstanding_amount);
        $this->assertStringContainsString('B One - Paid - Balance Rs 0.00', $addOn->refresh()->charged_player_payment_status);
        $this->assertStringContainsString('B Two - Unpaid - Balance Rs 400.00', $addOn->charged_player_payment_status);
        $this->assertSame(
            [$loserTwo->id],
            GameParticipant::query()
                ->outstanding()
                ->whereColumn('amount_paid', '<', 'total_due')
                ->pluck('id')
                ->all(),
        );
    }

    public function test_century_game_minute_rate_is_charged_to_one_loser(): void
    {
        $table = SnookerTable::create([
            'number' => 2,
            'name' => 'Table 2',
            'hourly_rate' => 10,
        ]);

        $session = GameSession::create([
            'snooker_table_id' => $table->id,
            'game_type' => 'century',
            'status' => 'checked_out',
            'started_at' => Carbon::parse('2026-07-29 20:00:00'),
            'ended_at' => Carbon::parse('2026-07-29 21:30:00'),
            'checked_out_at' => Carbon::parse('2026-07-29 21:35:00'),
            'frames_played' => 1,
            'frame_fee' => 100,
            'hourly_rate' => 10,
        ]);

        $first = $session->participants()->create([
            'player_name_snapshot' => 'Century Player 1',
            'team' => 'solo',
            'is_loser' => true,
        ]);
        $second = $session->participants()->create([
            'player_name_snapshot' => 'Century Player 2',
            'team' => 'solo',
        ]);

        $this->assertSame('900.00', $first->refresh()->base_amount);
        $this->assertSame('0.00', $second->refresh()->base_amount);
        $this->assertSame(900.0, $session->refresh()->grand_total);
        $this->assertSame('1h 30m', CenturyTime::label($session));
        $this->assertSame(90, CenturyTime::minutes($session));
    }

    public function test_daily_report_carries_petty_cash_into_next_day_counter_cash(): void
    {
        CashDeposit::create([
            'deposit_date' => '2026-07-29',
            'opening_petty_cash' => 0,
            'amount_collected_from_staff' => 500,
            'petty_cash_kept' => 300,
            'bank_deposit_amount' => 500,
        ]);

        Payment::create([
            'payment_date' => '2026-07-30',
            'payment_method' => 'cash',
            'amount' => 1000,
        ]);

        Expense::create([
            'expense_date' => '2026-07-30',
            'category' => 'General',
            'description' => 'Counter expense',
            'amount' => 150,
            'paid_from' => 'cash',
        ]);

        $report = app(ReportService::class)->daily('2026-07-30');

        $this->assertSame(300.0, $report['opening_petty_cash']);
        $this->assertSame(1000.0, $report['cash_collected']);
        $this->assertSame(150.0, $report['expense_total']);
        $this->assertSame(1150.0, $report['counter_cash_expected']);
        $this->assertSame(850.0, $report['expected_owner_collection']);
    }

    public function test_manual_register_closing_is_used_for_reports_and_separate_staff_advance(): void
    {
        foreach ([1, 2, 3, 4] as $number) {
            SnookerTable::create([
                'number' => $number,
                'name' => 'Table '.$number,
                'hourly_rate' => 10,
            ]);
        }

        $staff = Staff::create([
            'name' => 'Sale Manager',
            'commission_rate' => 25,
        ]);

        Payment::create([
            'payment_date' => '2026-07-30',
            'payment_method' => 'cash',
            'amount' => 9999,
        ]);

        Expense::create([
            'expense_date' => '2026-07-30',
            'category' => 'General',
            'description' => 'Detailed expense ignored by manual closing',
            'amount' => 9999,
            'paid_from' => 'cash',
        ]);

        $closing = CashDeposit::create([
            'deposit_date' => '2026-07-30',
            'closing_source' => 'manual',
            'opening_petty_cash' => 100,
            'manual_table_1_sale' => 1000,
            'manual_table_2_sale' => 800,
            'manual_table_3_sale' => 700,
            'manual_table_4_sale' => 500,
            'manual_expense_total' => 300,
            'cash_collected_from_counter' => 2700,
            'amount_collected_from_staff' => 2500,
            'petty_cash_kept' => 200,
            'bank_deposit_amount' => 2500,
        ]);

        $advance = StaffTransaction::create([
            'staff_id' => $staff->id,
            'transaction_date' => '2026-07-30',
            'commission_month' => '2026-07-01',
            'type' => 'advance',
            'paid_from' => 'cash',
            'amount' => 250,
            'description' => 'Staff advance',
        ]);

        $this->assertSame($staff->id, $advance->staff_id);
        $this->assertNull($advance->cash_deposit_id);
        $this->assertSame('advance', $advance->type);
        $this->assertSame('cash', $advance->paid_from);
        $this->assertSame('250.00', $advance->amount);
        $this->assertSame('2026-07-01', $advance->commission_month->toDateString());
        $this->assertSame(0, StaffTransaction::query()->where('cash_deposit_id', $closing->id)->count());

        $daily = app(ReportService::class)->daily('2026-07-30');

        $this->assertSame('manual', $daily['closing_source']);
        $this->assertSame(3000.0, $daily['sales_total']);
        $this->assertSame(2500.0, $daily['cash_collected']);
        $this->assertSame(300.0, $daily['expense_total']);
        $this->assertSame(250.0, $daily['staff_paid_total']);
        $this->assertSame(2800.0, $daily['counter_cash_expected']);
        $this->assertSame(2800.0, $daily['counter_cash_collected']);
        $this->assertSame(1000.0, (float) $daily['table_sales']->first()['sales']);

        $monthly = app(ReportService::class)->monthly('2026-07');

        $this->assertSame(3000.0, $monthly['sales_total']);
        $this->assertSame(300.0, $monthly['expense_total']);
        $this->assertSame(250.0, $monthly['staff_paid_total']);
        $this->assertSame(2500.0, $monthly['cash_collected']);
        $this->assertSame(2500.0, $monthly['collection_after_rent']);
        $this->assertSame(2500.0, $monthly['net_profit']);
        $this->assertSame(2500.0, $monthly['commission_distribution_base']);
        $this->assertSame(625.0, $monthly['staff_shares'][0]['monthly_share']);
        $this->assertSame(375.0, $monthly['staff_shares'][0]['remaining_balance']);

        $commission = app(ReportService::class)->generateMonthlyCommission($staff, '2026-07');

        $this->assertSame('625.00', $commission->commission_amount);
        $this->assertSame('250.00', $commission->advances_deducted);
        $this->assertSame('375.00', $commission->balance_due);
    }

    public function test_game_session_closing_uses_sessions_for_sales_and_closing_for_cash(): void
    {
        foreach ([1, 2, 3, 4] as $number) {
            SnookerTable::create([
                'number' => $number,
                'name' => 'Table '.$number,
                'hourly_rate' => 10,
            ]);
        }

        $session = GameSession::create([
            'snooker_table_id' => SnookerTable::query()->where('number', 2)->value('id'),
            'game_type' => 'one_to_one',
            'status' => 'checked_out',
            'started_at' => Carbon::parse('2026-07-30 18:00:00'),
            'ended_at' => Carbon::parse('2026-07-30 19:00:00'),
            'checked_out_at' => Carbon::parse('2026-07-30 19:05:00'),
            'frames_played' => 2,
            'frame_fee' => 300,
            'hourly_rate' => 10,
        ]);

        $loser = $session->participants()->create([
            'player_name_snapshot' => 'Session loser',
            'team' => 'solo',
            'is_loser' => true,
        ]);
        $session->participants()->create([
            'player_name_snapshot' => 'Session winner',
            'team' => 'solo',
        ]);

        $loser->payments()->create([
            'payment_date' => '2026-07-30',
            'payment_method' => 'cash',
            'amount' => 400,
        ]);

        $closing = CashDeposit::create([
            'deposit_date' => '2026-07-30',
            'closing_source' => 'game_sessions',
            'amount_collected_from_staff' => 350,
            'cash_collected_from_counter' => 350,
        ]);

        Expense::create([
            'cash_deposit_id' => $closing->id,
            'expense_date' => '2026-07-30',
            'category' => 'General',
            'description' => 'Counter expense',
            'amount' => 50,
            'paid_from' => 'cash',
        ]);

        $daily = app(ReportService::class)->daily('2026-07-30');

        $this->assertSame('game_sessions', $daily['closing_source']);
        $this->assertSame('Checked-out game sessions closing', $daily['source_label']);
        $this->assertSame(600.0, $daily['gross_sales_total']);
        $this->assertSame(600.0, (float) $daily['table_sales']->firstWhere('table.number', 2)['sales']);
        $this->assertSame(200.0, $daily['dues_added']);
        $this->assertSame(400.0, $daily['sales_total']);
        $this->assertSame(50.0, $daily['expense_total']);
        $this->assertSame(350.0, $daily['cash_collected']);
        $this->assertSame(350.0, $daily['net_cash_profit']);
    }

    public function test_staff_advance_can_be_split_between_all_active_staff(): void
    {
        $firstStaff = Staff::create([
            'name' => 'First Manager',
            'commission_rate' => 25,
        ]);
        $secondStaff = Staff::create([
            'name' => 'Second Manager',
            'commission_rate' => 25,
        ]);
        Staff::create([
            'name' => 'Inactive Manager',
            'commission_rate' => 25,
            'is_active' => false,
        ]);

        StaffTransactionCreator::create([
            'split_between_all_staff' => true,
            'transaction_date' => '2026-07-30',
            'commission_month' => '2026-07-01',
            'type' => 'advance',
            'paid_from' => 'cash',
            'amount' => 101,
        ]);

        $advances = StaffTransaction::query()
            ->where('type', 'advance')
            ->orderBy('staff_id')
            ->get();

        $this->assertCount(2, $advances);
        $this->assertEqualsCanonicalizing(
            [$firstStaff->id, $secondStaff->id],
            $advances->pluck('staff_id')->all(),
        );
        $this->assertSame(['50.50', '50.50'], $advances->pluck('amount')->all());
        $this->assertSame(['cash', 'cash'], $advances->pluck('paid_from')->all());
        $this->assertEquals([null, null], $advances->pluck('cash_deposit_id')->all());

        $monthly = app(ReportService::class)->monthly('2026-07');

        $this->assertSame(101.0, $monthly['staff_paid_total']);
        $this->assertSame(50.5, $monthly['staff_shares'][0]['advance_paid']);
        $this->assertSame(50.5, $monthly['staff_shares'][1]['advance_paid']);
        $this->assertSame(0, BankTransaction::query()
            ->where('source_type', BankTransaction::SOURCE_STAFF_TRANSACTION)
            ->count());
    }

    public function test_daily_closing_cash_is_deposited_to_bank_only_when_bank_transaction_is_recorded(): void
    {
        $closing = CashDeposit::create([
            'deposit_date' => '2026-07-30',
            'closing_source' => 'manual',
            'manual_table_1_sale' => 1500,
            'amount_collected_from_staff' => 1200,
            'bank_deposit_amount' => 1200,
        ]);

        $this->assertDatabaseCount('bank_transactions', 0);

        $julyBankSummary = app(ReportService::class)->bankSummary('2026-07-30');
        $julyDaily = app(ReportService::class)->daily('2026-07-30');

        $this->assertSame(0.0, $julyBankSummary['cash_in_bank']);
        $this->assertSame(1200.0, $julyBankSummary['collection_cash_pending_deposit']);
        $this->assertSame(0.0, $julyDaily['bank_deposit_amount']);

        $transaction = BankTransaction::create([
            'transaction_date' => '2026-08-02',
            'type' => 'daily_collection_deposit',
            'amount' => 1200,
            'description' => 'July 30 collection deposited later',
        ]);

        $this->assertSame('daily_collection_deposit', $transaction->type);
        $this->assertNull($transaction->source_type);
        $this->assertNull($transaction->source_id);
        $this->assertSame('1200.00', $transaction->amount);
        $this->assertSame(1200.0, $transaction->signed_amount);

        $closing->update([
            'amount_collected_from_staff' => 1000,
            'bank_deposit_amount' => 1000,
        ]);

        $summary = app(ReportService::class)->bankSummary('2026-08-02');
        $depositDay = app(ReportService::class)->daily('2026-08-02');

        $this->assertSame(1, BankTransaction::query()->count());
        $this->assertSame(1200.0, $summary['cash_in_bank']);
        $this->assertSame(1200.0, $summary['daily_deposits']);
        $this->assertSame(1200.0, $depositDay['bank_deposit_amount']);

        $closing->delete();

        $this->assertDatabaseCount('bank_transactions', 1);
    }

    public function test_bank_paid_installments_and_expenses_reduce_bank_balance(): void
    {
        BankTransaction::create([
            'transaction_date' => '2026-07-01',
            'type' => 'loan_received',
            'amount' => 5000,
            'description' => 'Loan received in bank',
        ]);

        $supplier = CapitalLiability::create([
            'start_date' => '2026-07-01',
            'title' => 'Table supplier',
            'source_type' => 'supplier',
            'lender_name' => 'Supplier',
            'category' => 'Equipment',
            'principal_amount' => 2000,
            'installment_amount' => 500,
            'installment_frequency' => 'monthly',
            'status' => 'active',
        ]);

        $payment = CapitalLiabilityPayment::create([
            'capital_liability_id' => $supplier->id,
            'payment_date' => '2026-07-05',
            'amount' => 500,
            'paid_from' => 'bank',
        ]);

        Expense::create([
            'expense_date' => '2026-07-06',
            'category' => 'Utilities',
            'description' => 'Internet bill',
            'amount' => 300,
            'paid_from' => 'bank',
        ]);

        $summary = app(ReportService::class)->bankSummary('2026-07-31');

        $this->assertSame(5000.0, $summary['loan_received']);
        $this->assertSame(500.0, $summary['supplier_installments_paid']);
        $this->assertSame(300.0, $summary['expenses_paid']);
        $this->assertSame(4200.0, $summary['cash_in_bank']);
        $this->assertSame('supplier_installment_paid', $payment->bankTransaction()->firstOrFail()->type);

        $payment->update(['paid_from' => 'cash']);

        $summaryAfterUpdate = app(ReportService::class)->bankSummary('2026-07-31');

        $this->assertSame(0.0, $summaryAfterUpdate['supplier_installments_paid']);
        $this->assertSame(4700.0, $summaryAfterUpdate['cash_in_bank']);
    }

    public function test_owner_paid_liability_payment_creates_capital_recovery_record_without_bank_or_cash_effect(): void
    {
        $liability = CapitalLiability::create([
            'start_date' => '2026-07-01',
            'title' => 'AC Invertors',
            'source_type' => 'supplier',
            'lender_name' => 'Supplier',
            'category' => 'Equipment',
            'principal_amount' => 150000,
            'installment_amount' => 50000,
            'installment_frequency' => 'monthly',
            'status' => 'active',
        ]);

        CashDeposit::create([
            'deposit_date' => '2026-07-05',
            'closing_source' => 'manual',
            'manual_table_1_sale' => 10000,
            'amount_collected_from_staff' => 10000,
        ]);

        $payment = CapitalLiabilityPayment::create([
            'capital_liability_id' => $liability->id,
            'payment_date' => '2026-07-10',
            'amount' => 50000,
            'paid_from' => 'owner',
            'notes' => 'Paid personally by owner',
        ]);

        $capitalEntry = $payment->ownerCapital()->firstOrFail();
        $bankSummary = app(ReportService::class)->bankSummary('2026-07-31');
        $capitalSummary = app(ReportService::class)->capitalSummary('2026-07-31');
        $paymentDay = app(ReportService::class)->daily('2026-07-10');

        $this->assertSame(OwnerCapital::SOURCE_CAPITAL_LIABILITY_PAYMENT, $capitalEntry->source_type);
        $this->assertSame($payment->id, $capitalEntry->source_id);
        $this->assertSame('investment', $capitalEntry->type);
        $this->assertSame('50000.00', $capitalEntry->amount);
        $this->assertSame('Owner / other source paid liability: AC Invertors', $capitalEntry->description);
        $this->assertNull($payment->bankTransaction()->first());
        $this->assertSame(10000.0, $bankSummary['collection_cash_pending_deposit']);
        $this->assertSame(0.0, $bankSummary['cash_installments_pending_deduction']);
        $this->assertSame(50000.0, $capitalSummary['capital_added']);
        $this->assertSame(50000.0, $capitalSummary['capital_invested']);
        $this->assertSame(50000.0, $paymentDay['capital_installments_paid']);
        $this->assertSame(0.0, $paymentDay['capital_installments_paid_from_business']);
        $this->assertSame(0.0, $paymentDay['owner_profit_after_capital_installments']);

        $payment->update([
            'amount' => 60000,
            'paid_from' => 'bank',
        ]);

        $this->assertSame(0, OwnerCapital::query()
            ->where('source_type', OwnerCapital::SOURCE_CAPITAL_LIABILITY_PAYMENT)
            ->where('source_id', $payment->id)
            ->count());
        $this->assertSame('supplier_installment_paid', $payment->bankTransaction()->firstOrFail()->type);
    }

    public function test_other_bank_payments_received_are_tracked_as_bank_inflow(): void
    {
        BankTransaction::create([
            'transaction_date' => '2026-07-10',
            'type' => 'other_payment_received',
            'amount' => 750,
            'description' => 'Locker rent received',
        ]);

        $summary = app(ReportService::class)->bankSummary('2026-07-31');

        $this->assertSame(750.0, $summary['other_payments_received']);
        $this->assertSame(750.0, $summary['cash_in_bank']);
        $this->assertSame(750.0, $summary['total_inflows']);
    }

    public function test_bank_transaction_types_are_grouped_by_credit_and_debit(): void
    {
        $creditOptions = BankTransaction::typeOptionsForEntrySide('credit');
        $debitOptions = BankTransaction::typeOptionsForEntrySide('debit');

        $this->assertSame('credit', BankTransaction::entrySideForType('daily_collection_deposit'));
        $this->assertSame('debit', BankTransaction::entrySideForType('expense_paid'));
        $this->assertArrayHasKey('daily_collection_deposit', $creditOptions);
        $this->assertArrayHasKey('other_payment_received', $creditOptions);
        $this->assertArrayNotHasKey('expense_paid', $creditOptions);
        $this->assertArrayHasKey('expense_paid', $debitOptions);
        $this->assertArrayHasKey('loan_installment_paid', $debitOptions);
        $this->assertArrayNotHasKey('daily_collection_deposit', $debitOptions);
    }

    public function test_bank_paid_staff_transactions_reduce_bank_balance(): void
    {
        $staff = Staff::create([
            'name' => 'Bank Paid Staff',
            'commission_rate' => 25,
        ]);

        BankTransaction::create([
            'transaction_date' => '2026-07-10',
            'type' => 'other_payment_received',
            'amount' => 1000,
            'description' => 'Opening bank money',
        ]);

        $transaction = StaffTransaction::create([
            'staff_id' => $staff->id,
            'transaction_date' => '2026-07-11',
            'commission_month' => '2026-07-01',
            'type' => 'advance',
            'paid_from' => 'bank',
            'amount' => 250,
            'description' => 'Bank staff advance',
        ]);

        $summary = app(ReportService::class)->bankSummary('2026-07-31');

        $this->assertSame(250.0, $summary['staff_payments']);
        $this->assertSame(750.0, $summary['cash_in_bank']);
        $this->assertSame('staff_payment', $transaction->bankTransaction()->firstOrFail()->type);

        $transaction->update(['paid_from' => 'cash']);

        $summaryAfterUpdate = app(ReportService::class)->bankSummary('2026-07-31');

        $this->assertSame(0.0, $summaryAfterUpdate['staff_payments']);
        $this->assertSame(1000.0, $summaryAfterUpdate['cash_in_bank']);
    }

    public function test_cash_staff_transactions_reduce_pending_bank_deposit_and_digital_sources_create_bank_entry(): void
    {
        $staff = Staff::create([
            'name' => 'Flexible Paid Staff',
            'commission_rate' => 25,
        ]);

        CashDeposit::create([
            'deposit_date' => '2026-07-10',
            'closing_source' => 'manual',
            'manual_table_1_sale' => 1000,
            'amount_collected_from_staff' => 1000,
        ]);

        BankTransaction::create([
            'transaction_date' => '2026-07-10',
            'type' => 'other_payment_received',
            'amount' => 1000,
            'description' => 'Opening digital money',
        ]);

        $transaction = StaffTransaction::create([
            'staff_id' => $staff->id,
            'transaction_date' => '2026-07-11',
            'commission_month' => '2026-07-01',
            'type' => 'advance',
            'paid_from' => 'cash',
            'amount' => 250,
            'description' => 'Cash staff advance',
        ]);

        $cashSummary = app(ReportService::class)->bankSummary('2026-07-31');

        $this->assertSame(750.0, $cashSummary['collection_cash_pending_deposit']);
        $this->assertSame(250.0, $cashSummary['cash_staff_payments_pending_deduction']);
        $this->assertSame(0.0, $cashSummary['staff_payments']);
        $this->assertSame(1000.0, $cashSummary['cash_in_bank']);
        $this->assertNull($transaction->bankTransaction()->first());

        $transaction->update(['paid_from' => 'easy_paisa']);

        $digitalSummary = app(ReportService::class)->bankSummary('2026-07-31');
        $bankTransaction = $transaction->bankTransaction()->firstOrFail();

        $this->assertSame(1000.0, $digitalSummary['collection_cash_pending_deposit']);
        $this->assertSame(0.0, $digitalSummary['cash_staff_payments_pending_deduction']);
        $this->assertSame(250.0, $digitalSummary['staff_payments']);
        $this->assertSame(750.0, $digitalSummary['cash_in_bank']);
        $this->assertSame('staff_payment', $bankTransaction->type);
        $this->assertSame('Paid from EasyPaisa', $bankTransaction->notes);
    }

    public function test_cash_rent_paid_from_monthly_closing_reduces_pending_bank_deposit(): void
    {
        CashDeposit::create([
            'deposit_date' => '2026-07-10',
            'closing_source' => 'manual',
            'manual_table_1_sale' => 50000,
            'amount_collected_from_staff' => 50000,
        ]);

        MonthlyClosing::create([
            'month' => '2026-07-01',
            'status' => MonthlyClosing::STATUS_DRAFT,
            'rent_total' => 30000,
            'rent_paid_amount' => 30000,
            'rent_paid_from' => 'cash',
            'construction_deduction_amount' => 0,
            'construction_received_amount' => 0,
            'liabilities_verified' => false,
        ]);

        $summary = app(ReportService::class)->bankSummary('2026-07-31');

        $this->assertSame(30000.0, $summary['cash_rent_payments_pending_deduction']);
        $this->assertSame(20000.0, $summary['collection_cash_pending_deposit']);
        $this->assertSame(0.0, $summary['rent_paid']);
        $this->assertSame(0, BankTransaction::query()->count());
    }

    public function test_cash_expenses_and_cash_installments_reduce_pending_bank_deposit(): void
    {
        $closing = CashDeposit::create([
            'deposit_date' => '2026-07-10',
            'closing_source' => 'manual',
            'manual_table_1_sale' => 50000,
            'manual_expense_total' => 3000,
            'amount_collected_from_staff' => 50000,
        ]);

        Expense::create([
            'cash_deposit_id' => $closing->id,
            'expense_date' => '2026-07-10',
            'category' => 'General',
            'description' => 'Daily closing expense',
            'amount' => 3000,
            'paid_from' => 'cash',
        ]);

        Expense::create([
            'expense_date' => '2026-07-12',
            'category' => 'General',
            'description' => 'Cash repair payment',
            'amount' => 7000,
            'paid_from' => 'cash',
        ]);

        $liability = CapitalLiability::create([
            'start_date' => '2026-07-01',
            'title' => 'Supplier balance',
            'source_type' => 'supplier',
            'lender_name' => 'Supplier',
            'category' => 'Equipment',
            'principal_amount' => 10000,
            'installment_amount' => 5000,
            'installment_frequency' => 'monthly',
            'status' => 'active',
        ]);

        CapitalLiabilityPayment::create([
            'capital_liability_id' => $liability->id,
            'payment_date' => '2026-07-13',
            'amount' => 5000,
            'paid_from' => 'cash',
        ]);

        BankTransaction::create([
            'transaction_date' => '2026-07-14',
            'type' => 'daily_collection_deposit',
            'amount' => 20000,
            'description' => 'Cash deposited to bank',
        ]);

        $summary = app(ReportService::class)->bankSummary('2026-07-31');

        $this->assertSame(7000.0, $summary['cash_expenses_pending_deduction']);
        $this->assertSame(5000.0, $summary['cash_installments_pending_deduction']);
        $this->assertSame(0.0, $summary['construction_other_account_pending_deduction']);
        $this->assertSame(12000.0, $summary['cash_outflow_pending_deductions']);
        $this->assertSame(18000.0, $summary['collection_cash_pending_deposit']);
        $this->assertSame(20000.0, $summary['daily_deposits']);
        $this->assertSame(20000.0, $summary['cash_in_bank']);
    }

    public function test_rent_split_tracks_cash_paid_and_construction_saved_separately_for_pending_bank_deposit(): void
    {
        CashDeposit::create([
            'deposit_date' => '2026-07-10',
            'closing_source' => 'manual',
            'manual_table_1_sale' => 50000,
            'amount_collected_from_staff' => 50000,
        ]);

        Expense::create([
            'expense_date' => '2026-07-31',
            'category' => Expense::CATEGORY_RENT,
            'description' => 'Monthly Rent',
            'amount' => 30000,
            'paid_from' => 'cash',
        ]);

        MonthlyClosing::create([
            'month' => '2026-07-01',
            'status' => MonthlyClosing::STATUS_DRAFT,
            'rent_total' => 30000,
            'rent_paid_amount' => 15000,
            'rent_paid_from' => 'cash',
            'construction_deduction_amount' => 15000,
            'construction_received_amount' => 15000,
            'construction_account_name' => 'Construction recovery bank',
            'liabilities_verified' => false,
        ]);

        $summary = app(ReportService::class)->bankSummary('2026-07-31');
        $monthly = app(ReportService::class)->monthly('2026-07');

        $this->assertSame(15000.0, $summary['cash_rent_payments_pending_deduction']);
        $this->assertSame(0.0, $summary['cash_expenses_pending_deduction']);
        $this->assertSame(15000.0, $summary['construction_other_account_pending_deduction']);
        $this->assertSame(30000.0, $summary['cash_outflow_pending_deductions']);
        $this->assertSame(20000.0, $summary['collection_cash_pending_deposit']);
        $this->assertSame(30000.0, $monthly['rent_expense_total']);
        $this->assertSame(15000.0, $monthly['monthly_closing']['rent_paid_amount']);
        $this->assertSame(15000.0, $monthly['monthly_closing']['construction_deduction_amount']);
        $this->assertSame(15000.0, $monthly['monthly_closing']['construction_received_amount']);
        $this->assertSame(0.0, $monthly['monthly_closing']['construction_balance']);
    }

    public function test_staff_share_summary_deducts_overall_paid_amounts(): void
    {
        foreach ([1, 2, 3, 4] as $number) {
            SnookerTable::create([
                'number' => $number,
                'name' => 'Table '.$number,
                'hourly_rate' => 10,
            ]);
        }

        $staff = Staff::create([
            'name' => 'Sale Manager',
            'commission_rate' => 25,
        ]);

        CashDeposit::create([
            'deposit_date' => '2026-07-01',
            'staff_id' => $staff->id,
            'closing_source' => 'manual',
            'manual_table_1_sale' => 1000,
            'manual_expense_total' => 200,
            'cash_collected_from_counter' => 800,
            'amount_collected_from_staff' => 800,
        ]);

        StaffTransaction::create([
            'staff_id' => $staff->id,
            'transaction_date' => '2026-07-01',
            'commission_month' => '2026-07-01',
            'type' => 'advance',
            'paid_from' => 'cash',
            'amount' => 100,
        ]);

        MonthlyCommission::create([
            'staff_id' => $staff->id,
            'month' => '2026-07-01',
            'cash_collected' => 1000,
            'expense_total' => 200,
            'net_profit' => 800,
            'commission_rate' => 25,
            'commission_amount' => 200,
            'paid_amount' => 40,
            'balance_due' => 60,
            'generated_at' => now(),
        ]);

        StaffTransaction::create([
            'staff_id' => $staff->id,
            'transaction_date' => '2026-07-02',
            'commission_month' => '2026-07-01',
            'type' => 'payout',
            'amount' => 25,
            'description' => 'Extra commission payout',
        ]);

        $summary = app(ReportService::class)->staffShareSummary('2026-07-31');

        $this->assertSame(800.0, $summary['net_profit_to_date']);
        $this->assertSame(200.0, $summary['share_earned_total']);
        $this->assertSame(165.0, $summary['amount_paid_total']);
        $this->assertSame(35.0, $summary['balance_due_total']);
    }

    public function test_monthly_commission_uses_one_overall_pool_with_staff_bifurcation(): void
    {
        foreach ([1, 2, 3, 4] as $number) {
            SnookerTable::create([
                'number' => $number,
                'name' => 'Table '.$number,
                'hourly_rate' => 10,
            ]);
        }

        $firstStaff = Staff::create([
            'name' => 'First Manager',
            'commission_rate' => 25,
        ]);
        $secondStaff = Staff::create([
            'name' => 'Second Manager',
            'commission_rate' => 25,
        ]);

        CashDeposit::create([
            'deposit_date' => '2026-07-01',
            'closing_source' => 'manual',
            'manual_table_1_sale' => 1200,
            'manual_expense_total' => 200,
            'cash_collected_from_counter' => 1000,
            'amount_collected_from_staff' => 1000,
        ]);

        StaffTransaction::create([
            'staff_id' => $firstStaff->id,
            'transaction_date' => '2026-07-02',
            'commission_month' => '2026-07-01',
            'type' => 'advance',
            'amount' => 100,
        ]);
        StaffTransaction::create([
            'staff_id' => $secondStaff->id,
            'transaction_date' => '2026-07-03',
            'commission_month' => '2026-07-01',
            'type' => 'payout',
            'amount' => 50,
        ]);

        $monthly = app(ReportService::class)->monthly('2026-07');

        $this->assertSame(25.0, $monthly['overall_commission_rate']);
        $this->assertSame(1000.0, $monthly['net_profit']);
        $this->assertSame(250.0, $monthly['commission_estimate']);
        $this->assertSame(250.0, $monthly['staff_commission_totals']['monthly_share']);
        $this->assertSame(250.0, $monthly['staff_commission_totals']['monthly_commission_to_be_paid']);
        $this->assertSame(0.0, $monthly['staff_advance_carry_in']);
        $this->assertSame(150.0, $monthly['staff_paid_total']);
        $this->assertSame(100.0, $monthly['staff_distribution_to_be_paid']);
        $this->assertSame(0.0, $monthly['staff_advance_carry_forward']);
        $this->assertSame(150.0, $monthly['staff_commission_totals']['total_paid']);
        $this->assertSame(150.0, $monthly['staff_commission_totals']['already_paid_this_month']);
        $this->assertSame(100.0, $monthly['staff_commission_totals']['monthly_remaining']);
        $this->assertSame(100.0, $monthly['staff_commission_totals']['total_to_be_paid_this_month']);

        $this->assertSame(50.0, $monthly['staff_shares'][0]['distribution_rate']);
        $this->assertSame(12.5, $monthly['staff_shares'][0]['commission_rate']);
        $this->assertSame(125.0, $monthly['staff_shares'][0]['monthly_share']);
        $this->assertSame(125.0, $monthly['staff_shares'][0]['monthly_commission_to_be_paid']);
        $this->assertSame(100.0, $monthly['staff_shares'][0]['advance_paid']);
        $this->assertSame(100.0, $monthly['staff_shares'][0]['already_paid_this_month']);
        $this->assertSame(25.0, $monthly['staff_shares'][0]['monthly_remaining']);
        $this->assertSame(25.0, $monthly['staff_shares'][0]['total_to_be_paid_this_month']);

        $this->assertSame(50.0, $monthly['staff_shares'][1]['distribution_rate']);
        $this->assertSame(12.5, $monthly['staff_shares'][1]['commission_rate']);
        $this->assertSame(125.0, $monthly['staff_shares'][1]['monthly_share']);
        $this->assertSame(50.0, $monthly['staff_shares'][1]['payout_paid']);
        $this->assertSame(50.0, $monthly['staff_shares'][1]['already_paid_this_month']);
        $this->assertSame(75.0, $monthly['staff_shares'][1]['monthly_remaining']);
        $this->assertSame(75.0, $monthly['staff_shares'][1]['total_to_be_paid_this_month']);
    }

    public function test_monthly_commission_deducts_rent_entered_on_manual_closing_day(): void
    {
        foreach ([1, 2, 3, 4] as $number) {
            SnookerTable::create([
                'number' => $number,
                'name' => 'Table '.$number,
                'hourly_rate' => 10,
            ]);
        }

        Staff::create([
            'name' => 'Sale Manager',
            'commission_rate' => 25,
        ]);

        CashDeposit::create([
            'deposit_date' => '2026-07-05',
            'closing_source' => 'manual',
            'manual_table_1_sale' => 100000,
            'manual_expense_total' => 1000,
            'cash_collected_from_counter' => 99000,
            'amount_collected_from_staff' => 99000,
        ]);

        Expense::create([
            'expense_date' => '2026-07-05',
            'category' => Expense::CATEGORY_RENT,
            'description' => 'Monthly rent',
            'amount' => Expense::scheduledRentForDate('2026-07-05'),
            'paid_from' => 'bank',
        ]);

        $daily = app(ReportService::class)->daily('2026-07-05');
        $monthly = app(ReportService::class)->monthly('2026-07');
        $yearly = app(ReportService::class)->yearly('2026-01-01');
        $july = collect($yearly['months'])->first(fn (array $month): bool => $month['month']->format('Y-m') === '2026-07');

        $this->assertSame(30000.0, Expense::scheduledRentForDate('2026-07-05'));
        $this->assertSame(33000.0, Expense::scheduledRentForDate('2027-06-01'));
        $this->assertSame(1000.0, $daily['expense_total']);
        $this->assertSame(30000.0, $daily['rent_expense_total']);
        $this->assertSame(1000.0, $monthly['daily_expense_total']);
        $this->assertSame(30000.0, $monthly['rent_expense_total']);
        $this->assertSame(31000.0, $monthly['expense_total']);
        $this->assertSame(69000.0, $monthly['collection_after_rent']);
        $this->assertSame(69000.0, $monthly['net_profit']);
        $this->assertSame(69000.0, $monthly['commission_distribution_base']);
        $this->assertSame(17250.0, $monthly['commission_estimate']);
        $this->assertSame(17250.0, $monthly['staff_shares'][0]['monthly_share']);
        $this->assertSame(30000.0, $july['rent_expense_total']);
        $this->assertSame(69000.0, $july['collection_after_rent']);
        $this->assertSame(69000.0, $july['commission_distribution_base']);
        $this->assertSame(17250.0, $july['commission_estimate']);
    }

    public function test_monthly_commission_deducts_standalone_rent_from_monthly_pool(): void
    {
        foreach ([1, 2, 3, 4] as $number) {
            SnookerTable::create([
                'number' => $number,
                'name' => 'Table '.$number,
                'hourly_rate' => 10,
            ]);
        }

        Staff::create([
            'name' => 'Sale Manager',
            'commission_rate' => 25,
        ]);

        Expense::create([
            'expense_date' => '2026-07-01',
            'category' => Expense::CATEGORY_RENT,
            'description' => 'July Rent',
            'amount' => 30000,
            'paid_from' => 'bank',
        ]);

        CashDeposit::create([
            'deposit_date' => '2026-07-05',
            'closing_source' => 'manual',
            'manual_table_1_sale' => 100000,
            'cash_collected_from_counter' => 100000,
            'amount_collected_from_staff' => 100000,
        ]);

        $monthly = app(ReportService::class)->monthly('2026-07');

        $this->assertSame(30000.0, $monthly['rent_expense_total']);
        $this->assertSame(30000.0, $monthly['expense_total']);
        $this->assertSame(70000.0, $monthly['collection_after_rent']);
        $this->assertSame(70000.0, $monthly['net_profit']);
        $this->assertSame(70000.0, $monthly['commission_distribution_base']);
        $this->assertSame(17500.0, $monthly['commission_estimate']);
        $this->assertSame(17500.0, $monthly['staff_shares'][0]['monthly_share']);
    }

    public function test_dashboard_staff_summary_can_include_scheduled_monthly_rent_preview(): void
    {
        foreach ([1, 2, 3, 4] as $number) {
            SnookerTable::create([
                'number' => $number,
                'name' => 'Table '.$number,
                'hourly_rate' => 10,
            ]);
        }

        Staff::create([
            'name' => 'Sale Manager',
            'commission_rate' => 25,
        ]);

        CashDeposit::create([
            'deposit_date' => '2026-08-05',
            'closing_source' => 'manual',
            'manual_table_1_sale' => 100000,
            'cash_collected_from_counter' => 100000,
            'amount_collected_from_staff' => 100000,
        ]);

        $reportService = app(ReportService::class);
        $plainBusiness = $reportService->businessSummary('2026-08-05');
        $dashboardBusiness = $reportService->businessSummary('2026-08-05', includeScheduledRent: true);
        $dashboardStaff = $reportService->staffShareSummary('2026-08-05', includeScheduledRent: true);
        $monthlyPreview = $reportService->monthly('2026-08', MonthlyClosing::defaultsForMonth('2026-08-01'));

        $this->assertSame(25000.0, $plainBusiness['staff_commission']);
        $this->assertSame(17500.0, $dashboardBusiness['staff_commission']);
        $this->assertSame(17500.0, $dashboardStaff['balance_due_total']);
        $this->assertSame(17500.0, $monthlyPreview['commission_estimate']);
        $this->assertSame(30000.0, $monthlyPreview['rent_expense_total']);
    }

    public function test_monthly_closing_rent_split_is_used_for_commission_distribution(): void
    {
        foreach ([1, 2, 3, 4] as $number) {
            SnookerTable::create([
                'number' => $number,
                'name' => 'Table '.$number,
                'hourly_rate' => 10,
            ]);
        }

        Staff::create([
            'name' => 'Sale Manager',
            'commission_rate' => 25,
        ]);

        CashDeposit::create([
            'deposit_date' => '2026-07-05',
            'closing_source' => 'manual',
            'manual_table_1_sale' => 100000,
            'cash_collected_from_counter' => 100000,
            'amount_collected_from_staff' => 100000,
        ]);

        $monthly = app(ReportService::class)->monthly('2026-07', [
            'month' => '2026-07-01',
            'rent_total' => 30000,
            'rent_paid_amount' => 15000,
            'rent_paid_from' => 'bank',
            'construction_deduction_amount' => 15000,
            'construction_received_amount' => 5000,
            'construction_account_name' => 'Other bank',
            'liabilities_verified' => true,
        ]);

        $this->assertSame(30000.0, $monthly['rent_expense_total']);
        $this->assertSame(70000.0, $monthly['collection_after_rent']);
        $this->assertSame(70000.0, $monthly['net_profit']);
        $this->assertSame(70000.0, $monthly['commission_distribution_base']);
        $this->assertSame(17500.0, $monthly['commission_estimate']);
        $this->assertSame(15000, $monthly['monthly_closing']['rent_paid_amount']);
        $this->assertSame(15000, $monthly['monthly_closing']['construction_deduction_amount']);
        $this->assertSame(10000.0, $monthly['monthly_closing']['construction_balance']);
    }

    public function test_closed_monthly_closing_records_only_paid_rent_in_bank(): void
    {
        $closing = MonthlyClosing::create([
            'month' => '2026-07-01',
            'status' => MonthlyClosing::STATUS_CLOSED,
            'rent_total' => 30000,
            'rent_paid_amount' => 15000,
            'rent_paid_from' => 'bank',
            'construction_deduction_amount' => 15000,
            'construction_received_amount' => 0,
            'liabilities_verified' => true,
            'closed_at' => now(),
        ]);

        $transaction = BankTransaction::query()
            ->where('source_type', BankTransaction::SOURCE_MONTHLY_CLOSING)
            ->where('source_id', $closing->id)
            ->firstOrFail();
        $summary = BankTransaction::summary('2026-07-31');

        $this->assertSame('rent_paid', $transaction->type);
        $this->assertSame('15000.00', $transaction->amount);
        $this->assertSame(15000.0, $summary['rent_paid']);
        $this->assertSame(-15000.0, $summary['cash_in_bank']);
    }

    public function test_commission_rates_are_applied_by_effective_date_within_month(): void
    {
        foreach ([1, 2, 3, 4] as $number) {
            SnookerTable::create([
                'number' => $number,
                'name' => 'Table '.$number,
                'hourly_rate' => 10,
            ]);
        }

        Staff::create([
            'name' => 'Sale Manager',
            'commission_rate' => 25,
        ]);

        CommissionRate::create([
            'rate' => 20,
            'effective_from' => '2026-07-01',
            'is_active' => true,
        ]);

        CommissionRate::create([
            'rate' => 30,
            'effective_from' => '2026-07-15',
            'is_active' => true,
        ]);

        CashDeposit::create([
            'deposit_date' => '2026-07-10',
            'closing_source' => 'manual',
            'manual_table_1_sale' => 1000,
            'cash_collected_from_counter' => 1000,
            'amount_collected_from_staff' => 1000,
        ]);

        CashDeposit::create([
            'deposit_date' => '2026-07-20',
            'closing_source' => 'manual',
            'manual_table_1_sale' => 1000,
            'cash_collected_from_counter' => 1000,
            'amount_collected_from_staff' => 1000,
        ]);

        $beforeRateChange = app(ReportService::class)->daily('2026-07-10');
        $afterRateChange = app(ReportService::class)->daily('2026-07-20');
        $monthly = app(ReportService::class)->monthly('2026-07');

        $this->assertSame(20.0, $beforeRateChange['overall_commission_rate']);
        $this->assertSame(200.0, $beforeRateChange['staff_share_estimate']);
        $this->assertSame(30.0, $afterRateChange['overall_commission_rate']);
        $this->assertSame(300.0, $afterRateChange['staff_share_estimate']);
        $this->assertSame(2000.0, $monthly['net_profit']);
        $this->assertSame(25.0, $monthly['overall_commission_rate']);
        $this->assertSame(500.0, $monthly['commission_estimate']);
        $this->assertSame(500.0, $monthly['staff_shares'][0]['monthly_share']);
    }

    public function test_staff_advance_overpayment_carries_into_next_month_distribution(): void
    {
        foreach ([1, 2, 3, 4] as $number) {
            SnookerTable::create([
                'number' => $number,
                'name' => 'Table '.$number,
                'hourly_rate' => 10,
            ]);
        }

        $staff = Staff::create([
            'name' => 'Sale Manager',
            'commission_rate' => 25,
        ]);

        CashDeposit::create([
            'deposit_date' => '2026-07-01',
            'closing_source' => 'manual',
            'manual_table_1_sale' => 400,
            'manual_expense_total' => 0,
            'cash_collected_from_counter' => 400,
            'amount_collected_from_staff' => 400,
        ]);

        StaffTransaction::create([
            'staff_id' => $staff->id,
            'transaction_date' => '2026-07-01',
            'commission_month' => '2026-07-01',
            'type' => 'advance',
            'paid_from' => 'cash',
            'amount' => 150,
        ]);

        CashDeposit::create([
            'deposit_date' => '2026-08-01',
            'closing_source' => 'manual',
            'manual_table_1_sale' => 400,
            'manual_expense_total' => 0,
            'cash_collected_from_counter' => 400,
            'amount_collected_from_staff' => 400,
        ]);

        $july = app(ReportService::class)->monthly('2026-07');
        $august = app(ReportService::class)->monthly('2026-08');

        $this->assertSame(100.0, $july['commission_estimate']);
        $this->assertSame(150.0, $july['staff_paid_total']);
        $this->assertSame(0.0, $july['staff_advance_carry_in']);
        $this->assertSame(-50.0, $july['staff_distribution_to_be_paid']);
        $this->assertSame(-50.0, $july['staff_advance_carry_forward']);

        $this->assertSame(100.0, $august['commission_estimate']);
        $this->assertSame(0.0, $august['staff_paid_total']);
        $this->assertSame(-50.0, $august['staff_advance_carry_in']);
        $this->assertSame(50.0, $august['staff_distribution_to_be_paid']);
        $this->assertSame(0.0, $august['staff_advance_carry_forward']);
    }

    public function test_owner_capital_is_recovered_from_profit_after_staff_share(): void
    {
        foreach ([1, 2, 3, 4] as $number) {
            SnookerTable::create([
                'number' => $number,
                'name' => 'Table '.$number,
                'hourly_rate' => 10,
            ]);
        }

        Staff::create([
            'name' => 'Sale Manager',
            'commission_rate' => 25,
        ]);

        OwnerCapital::create([
            'entry_date' => '2026-07-01',
            'type' => 'investment',
            'amount' => 1000,
            'description' => 'Opening capital',
        ]);

        CashDeposit::create([
            'deposit_date' => '2026-07-01',
            'closing_source' => 'manual',
            'opening_petty_cash' => 0,
            'manual_table_1_sale' => 1000,
            'manual_expense_total' => 200,
            'cash_collected_from_counter' => 800,
            'amount_collected_from_staff' => 800,
            'petty_cash_kept' => 0,
            'bank_deposit_amount' => 800,
        ]);

        CashDeposit::create([
            'deposit_date' => '2026-07-02',
            'closing_source' => 'manual',
            'opening_petty_cash' => 0,
            'manual_table_1_sale' => 1000,
            'manual_expense_total' => 0,
            'cash_collected_from_counter' => 1000,
            'amount_collected_from_staff' => 1000,
            'petty_cash_kept' => 0,
            'bank_deposit_amount' => 1000,
        ]);

        $firstDay = app(ReportService::class)->daily('2026-07-01');

        $this->assertSame(800.0, $firstDay['net_cash_profit']);
        $this->assertSame(200.0, $firstDay['staff_share_estimate']);
        $this->assertSame(600.0, $firstDay['owner_profit_after_staff_share']);
        $this->assertSame(600.0, $firstDay['capital']['capital_recovered']);
        $this->assertSame(400.0, $firstDay['capital']['capital_remaining']);

        $month = app(ReportService::class)->monthly('2026-07');

        $this->assertSame(1800.0, $month['net_profit']);
        $this->assertSame(450.0, $month['commission_estimate']);
        $this->assertSame(1350.0, $month['owner_profit_after_staff_share']);
        $this->assertSame(1000.0, $month['capital']['capital_invested']);
        $this->assertSame(1000.0, $month['capital']['capital_recovered']);
        $this->assertSame(0.0, $month['capital']['capital_remaining']);
    }

    public function test_capital_liability_installments_are_tracked_separately_from_owner_profit(): void
    {
        foreach ([1, 2, 3, 4] as $number) {
            SnookerTable::create([
                'number' => $number,
                'name' => 'Table '.$number,
                'hourly_rate' => 10,
            ]);
        }

        Staff::create([
            'name' => 'Sale Manager',
            'commission_rate' => 25,
        ]);

        OwnerCapital::create([
            'entry_date' => '2026-07-01',
            'type' => 'investment',
            'amount' => 1000,
        ]);

        $liability = CapitalLiability::create([
            'start_date' => '2026-07-01',
            'title' => 'Solar from friend',
            'source_type' => 'friend',
            'lender_name' => 'Friend',
            'category' => 'Solar',
            'principal_amount' => 500,
            'installment_amount' => 100,
            'installment_frequency' => 'monthly',
            'status' => 'active',
        ]);

        CapitalLiabilityPayment::create([
            'capital_liability_id' => $liability->id,
            'payment_date' => '2026-07-01',
            'amount' => 100,
            'paid_from' => 'cash',
        ]);

        CashDeposit::create([
            'deposit_date' => '2026-07-01',
            'closing_source' => 'manual',
            'opening_petty_cash' => 0,
            'manual_table_1_sale' => 1000,
            'manual_expense_total' => 0,
            'cash_collected_from_counter' => 1000,
            'amount_collected_from_staff' => 900,
            'petty_cash_kept' => 0,
            'bank_deposit_amount' => 900,
        ]);

        $daily = app(ReportService::class)->daily('2026-07-01');

        $this->assertSame(1000.0, $daily['net_cash_profit']);
        $this->assertSame(250.0, $daily['staff_share_estimate']);
        $this->assertSame(750.0, $daily['owner_profit_after_staff_share']);
        $this->assertSame(100.0, $daily['capital_installments_paid']);
        $this->assertSame(650.0, $daily['owner_profit_after_capital_installments']);
        $this->assertSame(900.0, $daily['counter_cash_expected']);
        $this->assertSame(500.0, $daily['liabilities']['principal_total']);
        $this->assertSame(100.0, $daily['liabilities']['paid_to_date']);
        $this->assertSame(400.0, $daily['liabilities']['balance_total']);
        $this->assertSame(0.0, $daily['liabilities']['loan_paid_to_date']);
        $this->assertSame(100.0, $daily['liabilities']['equipment_paid_to_date']);
        $this->assertSame(400.0, $daily['liabilities']['equipment_balance_total']);
        $this->assertSame(750.0, $daily['capital']['capital_recovered']);
        $this->assertSame(250.0, $daily['capital']['capital_remaining']);

        $monthly = app(ReportService::class)->monthly('2026-07');

        $this->assertSame(100.0, $monthly['capital_installments_paid']);
        $this->assertSame(650.0, $monthly['owner_profit_after_capital_installments']);
        $this->assertSame(750.0, $monthly['capital']['capital_recovered']);
        $this->assertSame(250.0, $monthly['capital']['capital_remaining']);
        $this->assertSame(400.0, $monthly['liabilities']['balance_total']);
    }

    public function test_daily_dues_are_added_recovered_and_reported_by_period(): void
    {
        foreach ([1, 2, 3, 4] as $number) {
            SnookerTable::create([
                'number' => $number,
                'name' => 'Table '.$number,
                'hourly_rate' => 10,
            ]);
        }

        CashDeposit::create([
            'deposit_date' => '2026-07-01',
            'closing_source' => 'manual',
            'opening_petty_cash' => 0,
            'manual_table_1_sale' => 1000,
            'dues_added' => 200,
            'dues_recovered' => 0,
            'cash_collected_from_counter' => 800,
            'amount_collected_from_staff' => 800,
            'petty_cash_kept' => 0,
            'bank_deposit_amount' => 800,
        ]);

        CashDeposit::create([
            'deposit_date' => '2026-07-02',
            'closing_source' => 'manual',
            'opening_petty_cash' => 0,
            'manual_table_1_sale' => 0,
            'dues_added' => 0,
            'dues_recovered' => 50,
            'cash_collected_from_counter' => 50,
            'amount_collected_from_staff' => 50,
            'petty_cash_kept' => 0,
            'bank_deposit_amount' => 50,
        ]);

        $firstDay = app(ReportService::class)->daily('2026-07-01');

        $this->assertSame(200.0, $firstDay['dues_added']);
        $this->assertSame(0.0, $firstDay['dues_recovered']);
        $this->assertSame(200.0, $firstDay['dues']['balance_total']);
        $this->assertSame(800.0, $firstDay['cash_collected']);

        $secondDay = app(ReportService::class)->daily('2026-07-02');

        $this->assertSame(0.0, $secondDay['dues_added']);
        $this->assertSame(50.0, $secondDay['dues_recovered']);
        $this->assertSame(-50.0, $secondDay['dues_net_change']);
        $this->assertSame(150.0, $secondDay['dues']['balance_total']);
        $this->assertSame(50.0, $secondDay['cash_collected']);

        $monthly = app(ReportService::class)->monthly('2026-07');

        $this->assertSame(200.0, $monthly['dues_added']);
        $this->assertSame(50.0, $monthly['dues_recovered']);
        $this->assertSame(150.0, $monthly['dues_net_change']);
        $this->assertSame(150.0, $monthly['dues']['balance_total']);

        $yearly = app(ReportService::class)->yearly('2026-01-01');

        $this->assertSame(200.0, $yearly['dues_added']);
        $this->assertSame(50.0, $yearly['dues_recovered']);
        $this->assertSame(150.0, $yearly['dues']['balance_total']);
    }

    public function test_customer_due_rows_are_stored_and_reduce_manual_cash_collected(): void
    {
        foreach ([1, 2, 3, 4] as $number) {
            SnookerTable::create([
                'number' => $number,
                'name' => 'Table '.$number,
                'hourly_rate' => 10,
            ]);
        }

        $closing = CashDeposit::create([
            'deposit_date' => '2026-07-03',
            'closing_source' => 'manual',
            'opening_petty_cash' => 0,
            'manual_table_1_sale' => 1000,
            'customer_dues' => [
                [
                    'customer_name' => 'Walk-in customer',
                    'amount' => 250,
                ],
            ],
            'dues_added' => 250,
            'dues_recovered' => 0,
            'cash_collected_from_counter' => 750,
            'amount_collected_from_staff' => 750,
            'petty_cash_kept' => 0,
            'bank_deposit_amount' => 750,
        ]);

        $customerDues = $closing->refresh()->customer_dues;

        $this->assertSame('Walk-in customer', $customerDues[0]['customer_name']);
        $this->assertSame(250.0, (float) $customerDues[0]['amount']);

        $daily = app(ReportService::class)->daily('2026-07-03');

        $this->assertSame(750.0, $daily['sales_total']);
        $this->assertSame(1000.0, $daily['gross_sales_total']);
        $this->assertSame(250.0, $daily['dues_added']);
        $this->assertSame(750.0, $daily['cash_collected']);
        $this->assertSame(750.0, $daily['total_collection']);
        $this->assertSame(750.0, $daily['counter_cash_expected']);
        $this->assertSame(187.5, $daily['staff_share_estimate']);

        $due = CustomerDue::query()->where('customer_name', 'Walk-in customer')->firstOrFail();

        $this->assertSame('250.00', $due->balance_due);
        $this->assertSame(1, $due->charges()->count());
    }

    public function test_customer_due_payment_from_daily_closing_adds_cash_and_updates_balance(): void
    {
        foreach ([1, 2, 3, 4] as $number) {
            SnookerTable::create([
                'number' => $number,
                'name' => 'Table '.$number,
                'hourly_rate' => 10,
            ]);
        }

        $due = CustomerDue::create([
            'customer_name' => 'Due Customer',
            'opening_balance' => 300,
        ]);

        $closing = CashDeposit::create([
            'deposit_date' => '2026-07-04',
            'closing_source' => 'manual',
            'opening_petty_cash' => 0,
            'manual_table_1_sale' => 1000,
            'dues_added' => 0,
            'dues_recovered' => 100,
            'cash_collected_from_counter' => 1100,
            'amount_collected_from_staff' => 1100,
            'petty_cash_kept' => 0,
            'bank_deposit_amount' => 1100,
        ]);

        CustomerDuePayment::create([
            'customer_due_id' => $due->id,
            'cash_deposit_id' => $closing->id,
            'payment_date' => '2026-07-04',
            'amount' => 100,
        ]);

        $daily = app(ReportService::class)->daily('2026-07-04');

        $this->assertSame('200.00', $due->refresh()->balance_due);
        $this->assertSame(100.0, $daily['dues_recovered']);
        $this->assertSame(1100.0, $daily['cash_collected']);
        $this->assertSame(1100.0, $daily['net_cash_profit']);
        $this->assertSame(275.0, $daily['staff_share_estimate']);
        $this->assertSame(825.0, $daily['owner_profit_after_staff_share']);
    }

    public function test_dues_summary_uses_customer_due_ledger_rows_when_closing_totals_are_stale(): void
    {
        $due = CustomerDue::create([
            'customer_name' => 'Ledger Customer',
        ]);

        $closing = CashDeposit::create([
            'deposit_date' => '2026-08-03',
            'closing_source' => 'manual',
            'dues_added' => 0,
            'dues_recovered' => 0,
        ]);

        CustomerDueCharge::create([
            'customer_due_id' => $due->id,
            'cash_deposit_id' => $closing->id,
            'charge_date' => '2026-08-03',
            'amount' => 300,
        ]);

        CustomerDuePayment::create([
            'customer_due_id' => $due->id,
            'cash_deposit_id' => $closing->id,
            'payment_date' => '2026-08-03',
            'amount' => 100,
        ]);

        $summary = app(ReportService::class)->duesSummary('2026-08-03');

        $this->assertSame(300.0, $summary['added_to_date']);
        $this->assertSame(100.0, $summary['recovered_to_date']);
        $this->assertSame(200.0, $summary['balance_total']);
    }

    public function test_monthly_commission_counts_customer_dues_only_when_paid(): void
    {
        foreach ([1, 2, 3, 4] as $number) {
            SnookerTable::create([
                'number' => $number,
                'name' => 'Table '.$number,
                'hourly_rate' => 10,
            ]);
        }

        Staff::create([
            'name' => 'Sale Manager',
            'commission_rate' => 25,
        ]);

        CashDeposit::create([
            'deposit_date' => '2026-07-10',
            'closing_source' => 'manual',
            'opening_petty_cash' => 0,
            'manual_table_1_sale' => 1000,
            'customer_dues' => [
                [
                    'customer_name' => 'Commission Due Customer',
                    'amount' => 300,
                ],
            ],
            'dues_added' => 300,
            'dues_recovered' => 0,
            'cash_collected_from_counter' => 1000,
            'amount_collected_from_staff' => 700,
            'petty_cash_kept' => 0,
            'bank_deposit_amount' => 700,
        ]);

        $due = CustomerDue::query()->where('customer_name', 'Commission Due Customer')->firstOrFail();

        $augustClosing = CashDeposit::create([
            'deposit_date' => '2026-08-01',
            'closing_source' => 'manual',
            'opening_petty_cash' => 0,
            'manual_table_1_sale' => 0,
            'dues_added' => 0,
            'dues_recovered' => 300,
            'cash_collected_from_counter' => 300,
            'amount_collected_from_staff' => 300,
            'petty_cash_kept' => 0,
            'bank_deposit_amount' => 300,
        ]);

        CustomerDuePayment::create([
            'customer_due_id' => $due->id,
            'cash_deposit_id' => $augustClosing->id,
            'payment_date' => '2026-08-01',
            'amount' => 300,
        ]);

        $july = app(ReportService::class)->monthly('2026-07');
        $august = app(ReportService::class)->monthly('2026-08');

        $this->assertSame(700.0, $july['sales_total']);
        $this->assertSame(300.0, $july['dues_added']);
        $this->assertSame(700.0, $july['cash_collected']);
        $this->assertSame(700.0, $july['total_collection']);
        $this->assertSame(700.0, $july['net_profit']);
        $this->assertSame(175.0, $july['commission_estimate']);

        $this->assertSame(300.0, $august['sales_total']);
        $this->assertSame(300.0, $august['dues_recovered']);
        $this->assertSame(300.0, $august['cash_collected']);
        $this->assertSame(300.0, $august['net_profit']);
        $this->assertSame(75.0, $august['commission_estimate']);
    }

    public function test_customer_dues_report_can_be_generated_as_pdf(): void
    {
        $due = CustomerDue::create([
            'customer_name' => 'PDF Customer',
            'opening_balance' => 100,
        ]);

        CustomerDueCharge::create([
            'customer_due_id' => $due->id,
            'charge_date' => '2026-08-01',
            'amount' => 50,
        ]);

        CustomerDuePayment::create([
            'customer_due_id' => $due->id,
            'payment_date' => '2026-08-02',
            'amount' => 25,
        ]);

        $pdf = app(CustomerDuePdfReport::class)->generate();

        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringContainsString('Customer Dues Report', $pdf);
        $this->assertStringContainsString('PDF Customer', $pdf);
        $this->assertStringContainsString('Total', $pdf);
        $this->assertStringContainsString('Rs 125.00', $pdf);
    }

    public function test_customer_dues_report_pdf_export_route_downloads_pdf(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'role' => 'admin',
            'password' => 'password',
        ]);

        CustomerDue::create([
            'customer_name' => 'Route PDF Customer',
            'opening_balance' => 100,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('customer-dues.export-pdf'));

        $response->assertOk();

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment; filename="customer-dues-report-', $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-1.4', $response->getContent());
        $this->assertStringContainsString('Route PDF Customer', $response->getContent());
    }

    public function test_sale_manager_can_manage_game_sessions_but_not_admin_operations(): void
    {
        $saleManager = User::create([
            'name' => 'Sale User',
            'email' => 'sale@example.test',
            'role' => 'sale_manager',
            'password' => 'password',
        ]);
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'owner@example.test',
            'role' => 'admin',
            'password' => 'password',
        ]);
        $table = SnookerTable::create([
            'number' => 1,
            'name' => 'Table 1',
            'hourly_rate' => 10,
        ]);
        $session = GameSession::create([
            'snooker_table_id' => $table->id,
            'game_type' => 'one_to_one',
            'status' => 'active',
            'started_at' => '2026-07-30 18:00:00',
            'frames_played' => 1,
            'frame_fee' => 100,
            'hourly_rate' => 10,
        ]);
        $participant = GameParticipant::create([
            'game_session_id' => $session->id,
            'player_name_snapshot' => 'Walk-in Player',
            'team' => 'solo',
        ]);
        $player = Player::create([
            'name' => 'Read Only Player',
        ]);
        $expense = Expense::create([
            'expense_date' => '2026-07-30',
            'category' => 'General',
            'description' => 'Read only expense',
            'amount' => 100,
            'paid_from' => 'cash',
        ]);

        $this->actingAs($saleManager);

        $this->assertTrue(GameSessionResource::canCreate());
        $this->assertTrue(GameSessionResource::canEdit($session));
        $this->assertFalse(GameSessionResource::canDelete($session));
        $this->assertFalse(GameParticipantResource::canCreate());
        $this->assertFalse(GameParticipantResource::canEdit($participant));
        $this->assertFalse(PlayerResource::canCreate());
        $this->assertFalse(PlayerResource::canEdit($player));
        $this->assertFalse(GameAddOnResource::canCreate());
        $this->assertFalse(ExpenseResource::canCreate());
        $this->assertFalse(ExpenseResource::canEdit($expense));
        $this->assertFalse(DailyReport::canAccess());

        $this->actingAs($admin);

        $this->assertTrue(GameSessionResource::canCreate());
        $this->assertTrue(GameSessionResource::canEdit($session));
        $this->assertTrue(GameSessionResource::canDelete($session));
        $this->assertTrue(GameParticipantResource::canCreate());
        $this->assertTrue(GameParticipantResource::canEdit($participant));
        $this->assertTrue(PlayerResource::canCreate());
        $this->assertTrue(PlayerResource::canEdit($player));
        $this->assertTrue(GameAddOnResource::canCreate());
        $this->assertTrue(ExpenseResource::canCreate());
        $this->assertTrue(ExpenseResource::canEdit($expense));
        $this->assertTrue(DailyReport::canAccess());
    }
}
