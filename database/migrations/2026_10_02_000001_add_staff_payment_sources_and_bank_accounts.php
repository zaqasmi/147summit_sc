<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_transactions', function (Blueprint $table): void {
            // Keep every existing row, primary key, and source reference intact.
            // The existing single-bank ledger becomes RF Account in place.
            $table->string('bank_account')->default('rf_account')->index();
        });
        Schema::table('monthly_commissions', function (Blueprint $table): void {
            // Historical closing payments did not record a source.
            $table->string('paid_from')->nullable();
            $table->date('paid_on')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('monthly_commissions', fn (Blueprint $table) => $table->dropColumn(['paid_from', 'paid_on']));
        Schema::table('bank_transactions', function (Blueprint $table): void {
            $table->dropIndex(['bank_account']);
            $table->dropColumn('bank_account');
        });
    }
};
