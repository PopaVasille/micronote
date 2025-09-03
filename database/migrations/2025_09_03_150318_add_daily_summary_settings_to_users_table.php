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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('daily_summary_enabled')->default(false)->after('settings');
            $table->string('daily_summary_time', 5)->default('08:00')->after('daily_summary_enabled');
            $table->string('daily_summary_timezone', 50)->default('Europe/Bucharest')->after('daily_summary_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'daily_summary_enabled',
                'daily_summary_time', 
                'daily_summary_timezone'
            ]);
        });
    }
};
