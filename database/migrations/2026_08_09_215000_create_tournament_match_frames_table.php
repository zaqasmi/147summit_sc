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
        Schema::create('tournament_match_frames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tournament_match_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('frame_number');
            $table->unsignedInteger('player1_score')->default(0);
            $table->unsignedInteger('player2_score')->default(0);
            $table->foreignId('winner_id')->nullable()->constrained('tournament_players')->nullOnDelete();
            $table->unsignedInteger('player1_highest_break')->default(0);
            $table->unsignedInteger('player2_highest_break')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tournament_match_id', 'frame_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_match_frames');
    }
};
