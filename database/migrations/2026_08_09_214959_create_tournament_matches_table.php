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
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_match_id')->nullable()->constrained('tournament_matches')->nullOnDelete();
            $table->foreignId('next_match_id')->nullable()->constrained('tournament_matches')->nullOnDelete();
            $table->string('next_match_slot')->nullable();
            $table->unsignedInteger('round_number')->default(1);
            $table->string('round_name')->default('Round 1');
            $table->unsignedInteger('match_number')->default(1);
            $table->string('table_number')->nullable();
            $table->foreignId('player1_id')->nullable()->constrained('tournament_players')->nullOnDelete();
            $table->foreignId('player2_id')->nullable()->constrained('tournament_players')->nullOnDelete();
            $table->foreignId('winner_id')->nullable()->constrained('tournament_players')->nullOnDelete();
            $table->string('match_format')->default('best_of_5');
            $table->string('status')->default('scheduled');
            $table->unsignedInteger('player1_frames')->default(0);
            $table->unsignedInteger('player2_frames')->default(0);
            $table->unsignedInteger('player1_highest_break')->default(0);
            $table->unsignedInteger('player2_highest_break')->default(0);
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tournament_id', 'round_number', 'match_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
