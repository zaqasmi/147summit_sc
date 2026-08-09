<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_rates', function (Blueprint $table): void {
            $table->id();
            $table->decimal('rate', 5, 2)->default(25);
            $table->date('effective_from')->index();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::table('commission_rates')->insert([
            'rate' => 25,
            'effective_from' => '2026-01-01',
            'is_active' => true,
            'notes' => 'Default commission rate',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_rates');
    }
};
