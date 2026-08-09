<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bank_transactions')) {
            DB::table('bank_transactions')
                ->where('source_type', 'cash_deposit')
                ->delete();
        }

        if (Schema::hasColumn('cash_deposits', 'bank_deposit_amount')) {
            DB::table('cash_deposits')->update([
                'bank_deposit_amount' => 0,
            ]);
        }
    }

    public function down(): void
    {
        //
    }
};
