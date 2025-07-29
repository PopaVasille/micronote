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
        Schema::table('reminders', function (Blueprint $table) {
            $table->renameColumn('remind_at', 'next_remind_at');
            $table->string('recurrence_rule', 50)->nullable()->after('next_remind_at');
            $table->timestamp('recurrence_ends_at')->nullable()->after('recurrence_rule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->renameColumn('next_remind_at', 'remind_at');
            $table->dropColumn(['recurrence_rule', 'recurrence_ends_at']);

        });
    }
};
