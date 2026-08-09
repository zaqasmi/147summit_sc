<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table): void {
            $table->id();
            $table->date('transaction_date')->index();
            $table->string('type')->index();
            $table->decimal('amount', 12, 2);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['source_type', 'source_id']);
            $table->index(['source_type', 'source_id']);
        });

        $this->backfillDailyClosingDeposits();
        $this->backfillBankInstallments();
        $this->backfillBankExpenses();
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }

    private function backfillDailyClosingDeposits(): void
    {
        DB::table('cash_deposits')
            ->orderBy('id')
            ->get()
            ->each(function ($deposit): void {
                $amount = (float) $deposit->bank_deposit_amount > 0
                    ? (float) $deposit->bank_deposit_amount
                    : (float) $deposit->amount_collected_from_staff;

                if ($amount <= 0) {
                    return;
                }

                DB::table('bank_transactions')->insert([
                    'transaction_date' => $deposit->deposit_date,
                    'type' => 'daily_collection_deposit',
                    'amount' => round($amount, 2),
                    'source_type' => 'cash_deposit',
                    'source_id' => $deposit->id,
                    'description' => 'Daily closing cash deposited',
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    private function backfillBankInstallments(): void
    {
        DB::table('capital_liability_payments')
            ->leftJoin('capital_liabilities', 'capital_liability_payments.capital_liability_id', '=', 'capital_liabilities.id')
            ->where('capital_liability_payments.paid_from', 'bank')
            ->orderBy('capital_liability_payments.id')
            ->select([
                'capital_liability_payments.id',
                'capital_liability_payments.payment_date',
                'capital_liability_payments.amount',
                'capital_liabilities.title',
                'capital_liabilities.category',
                'capital_liabilities.source_type',
            ])
            ->get()
            ->each(function ($payment): void {
                $amount = (float) $payment->amount;

                if ($amount <= 0) {
                    return;
                }

                DB::table('bank_transactions')->insert([
                    'transaction_date' => $payment->payment_date,
                    'type' => $this->installmentTransactionType($payment->category, $payment->source_type),
                    'amount' => round($amount, 2),
                    'source_type' => 'capital_liability_payment',
                    'source_id' => $payment->id,
                    'description' => trim('Bank installment: '.($payment->title ?? '')),
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    private function backfillBankExpenses(): void
    {
        DB::table('expenses')
            ->where('paid_from', 'bank')
            ->orderBy('id')
            ->get()
            ->each(function ($expense): void {
                $amount = (float) $expense->amount;

                if ($amount <= 0) {
                    return;
                }

                DB::table('bank_transactions')->insert([
                    'transaction_date' => $expense->expense_date,
                    'type' => 'expense_paid',
                    'amount' => round($amount, 2),
                    'source_type' => 'expense',
                    'source_id' => $expense->id,
                    'description' => trim('Bank expense: '.($expense->description ?? '')),
                    'notes' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    private function installmentTransactionType(?string $category, ?string $sourceType): string
    {
        if ($category === 'Loan') {
            return 'loan_installment_paid';
        }

        if ($sourceType === 'supplier') {
            return 'supplier_installment_paid';
        }

        return 'capital_installment_paid';
    }
};
