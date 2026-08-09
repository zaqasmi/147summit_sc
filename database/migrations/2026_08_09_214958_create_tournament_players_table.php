<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tournament_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('player_id')->nullable()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->string('father_name')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('club_name')->nullable();
            $table->string('district')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('cnic')->nullable();
            $table->string('registration_number');
            $table->decimal('registration_fee', 12, 2)->default(0);
            $table->string('fee_status')->default('unpaid');
            $table->date('payment_date')->nullable();
            $table->unsignedInteger('seed')->nullable();
            $table->string('avoid_group')->nullable();
            $table->decimal('ranking_points', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['tournament_id', 'registration_number']);
            $table->unique(['tournament_id', 'seed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_players');
    }
};
