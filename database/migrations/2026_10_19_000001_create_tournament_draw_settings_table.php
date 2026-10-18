<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_draw_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tournament_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('separated_player_ids')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_draw_settings');
    }
};
