<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_commissions', function (Blueprint $table): void {
            $table->date('period_end')->nullable()->after('month')->index();
        });
    }

    public function down(): void
    {
        Schema::table('monthly_commissions', function (Blueprint $table): void {
            $table->dropColumn('period_end');
        });
    }
};
