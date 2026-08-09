<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('snooker_tables')->where('hourly_rate', 600)->update(['hourly_rate' => 10]);
        DB::table('game_sessions')->where('game_type', 'century')->where('hourly_rate', 600)->update(['hourly_rate' => 10]);
    }

    public function down(): void
    {
        DB::table('snooker_tables')->where('hourly_rate', 10)->update(['hourly_rate' => 600]);
        DB::table('game_sessions')->where('game_type', 'century')->where('hourly_rate', 10)->update(['hourly_rate' => 600]);
    }
};
