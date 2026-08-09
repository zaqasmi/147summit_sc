<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('cash_deposits', 'staff_paid_to_all')) {
            return;
        }

        Schema::table('cash_deposits', function (Blueprint $table): void {
            $table->boolean('staff_paid_to_all')
                ->default(false)
                ->after('staff_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('cash_deposits', 'staff_paid_to_all')) {
            return;
        }

        Schema::table('cash_deposits', function (Blueprint $table): void {
            $table->dropColumn('staff_paid_to_all');
        });
    }
};
