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
            // Adăugare uuid unic
            $table->uuid('uuid')->unique()->after('id')->nullable();

            // Adăugare informații contact
            $table->string('phone', 20)->unique()->nullable()->after('email');
            $table->string('telegram_id', 100)->unique()->nullable()->after('phone');

            // Adăugare plan utilizator și limite
            $table->enum('plan', ['free', 'plus'])->default('free')->after('password');
            $table->integer('monthly_notes_limit')->default(200)->after('plan');
            $table->integer('notes_count')->default(0)->after('monthly_notes_limit');

            // Adăugare preferințe și management abonament
            $table->enum('notification_preference', ['email', 'telegram','whatsapp', 'none'])->default('telegram')->after('notes_count');
            $table->timestamp('subscription_ends_at')->nullable()->after('notification_preference');

            // Adăugare câmpuri suplimentare
            $table->boolean('is_active')->default(true)->after('remember_token');
            $table->json('settings')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'uuid',
                'phone',
                'telegram_id',
                'plan',
                'monthly_notes_limit',
                'notes_count',
                'notification_preference',
                'subscription_ends_at',
                'is_active',
                'settings'
            ]);
        });
    }
};
