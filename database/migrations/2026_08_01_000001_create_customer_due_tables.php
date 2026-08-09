<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_dues', function (Blueprint $table): void {
            $table->id();
            $table->string('customer_name')->unique();
            $table->string('phone')->nullable();
            $table->decimal('opening_balance', 12, 2)->default(0);
            $table->decimal('total_charged', 12, 2)->default(0);
            $table->decimal('total_paid', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_due_charges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_due_id')->constrained('customer_dues')->cascadeOnDelete();
            $table->foreignId('cash_deposit_id')->nullable()->constrained('cash_deposits')->cascadeOnDelete();
            $table->date('charge_date')->index();
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('customer_due_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('customer_due_id')->constrained('customer_dues')->cascadeOnDelete();
            $table->foreignId('cash_deposit_id')->nullable()->constrained('cash_deposits')->cascadeOnDelete();
            $table->date('payment_date')->index();
            $table->decimal('amount', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        $this->backfillChargesFromDailyClosings();
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_due_payments');
        Schema::dropIfExists('customer_due_charges');
        Schema::dropIfExists('customer_dues');
    }

    private function backfillChargesFromDailyClosings(): void
    {
        DB::table('cash_deposits')
            ->whereNotNull('customer_dues')
            ->orderBy('id')
            ->get()
            ->each(function (object $deposit): void {
                $rows = json_decode((string) $deposit->customer_dues, true);

                if (! is_array($rows)) {
                    return;
                }

                collect($rows)
                    ->map(fn (array $row): array => [
                        'customer_name' => trim((string) ($row['customer_name'] ?? '')),
                        'amount' => round((float) ($row['amount'] ?? 0), 2),
                    ])
                    ->filter(fn (array $row): bool => $row['customer_name'] !== '' && $row['amount'] > 0)
                    ->each(function (array $row) use ($deposit): void {
                        $customerDue = DB::table('customer_dues')
                            ->whereRaw('lower(customer_name) = ?', [mb_strtolower($row['customer_name'])])
                            ->first();

                        if (! $customerDue) {
                            $customerDueId = DB::table('customer_dues')->insertGetId([
                                'customer_name' => $row['customer_name'],
                                'opening_balance' => 0,
                                'total_charged' => 0,
                                'total_paid' => 0,
                                'balance_due' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        } else {
                            $customerDueId = $customerDue->id;
                        }

                        DB::table('customer_due_charges')->insert([
                            'customer_due_id' => $customerDueId,
                            'cash_deposit_id' => $deposit->id,
                            'charge_date' => $deposit->deposit_date,
                            'amount' => $row['amount'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    });
            });

        DB::table('customer_dues')
            ->orderBy('id')
            ->get()
            ->each(function (object $customerDue): void {
                $charged = (float) DB::table('customer_due_charges')
                    ->where('customer_due_id', $customerDue->id)
                    ->sum('amount');
                $paid = (float) DB::table('customer_due_payments')
                    ->where('customer_due_id', $customerDue->id)
                    ->sum('amount');
                $totalDue = (float) $customerDue->opening_balance + $charged;

                DB::table('customer_dues')
                    ->where('id', $customerDue->id)
                    ->update([
                        'total_charged' => round($charged, 2),
                        'total_paid' => round($paid, 2),
                        'balance_due' => round(max(0, $totalDue - $paid), 2),
                        'updated_at' => now(),
                    ]);
            });
    }
};
