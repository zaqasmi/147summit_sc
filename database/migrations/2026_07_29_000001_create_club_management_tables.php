<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('snooker_tables', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number')->unique();
            $table->string('name');
            $table->decimal('hourly_rate', 10, 2)->default(10);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('players', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable()->unique();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('role')->default('manager');
            $table->decimal('commission_rate', 5, 2)->default(25);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('add_on_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('unit_price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('expense_date')->index();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('category')->default('General');
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->string('paid_from')->default('cash');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('cash_deposits', function (Blueprint $table) {
            $table->id();
            $table->date('deposit_date')->index();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->decimal('opening_petty_cash', 12, 2)->default(0);
            $table->decimal('amount_collected_from_staff', 12, 2)->default(0);
            $table->decimal('petty_cash_kept', 12, 2)->default(0);
            $table->decimal('bank_deposit_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('transaction_date')->index();
            $table->date('commission_month')->nullable()->index();
            $table->string('type')->default('advance');
            $table->decimal('amount', 12, 2);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('monthly_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->date('month')->index();
            $table->decimal('cash_collected', 12, 2)->default(0);
            $table->decimal('expense_total', 12, 2)->default(0);
            $table->decimal('net_profit', 12, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(25);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('carried_forward_from_previous', 12, 2)->default(0);
            $table->decimal('advances_deducted', 12, 2)->default(0);
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->decimal('balance_due', 12, 2)->default(0);
            $table->timestamp('generated_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['staff_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_commissions');
        Schema::dropIfExists('staff_transactions');
        Schema::dropIfExists('cash_deposits');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('add_on_items');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('players');
        Schema::dropIfExists('snooker_tables');
    }
};
