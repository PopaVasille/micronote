<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Execută migrarea.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reminders', function (Blueprint $table) {
            // Cheia primară
            $table->id();

            // Foreign key către tabela notes
            $table->unsignedBigInteger('note_id');
            $table->foreign('note_id')->references('id')->on('notes')->onDelete('cascade');

            // Câmpuri principale
            $table->dateTime('remind_at');
            $table->enum('reminder_type', ['email', 'telegram','whatsapp'])->default('telegram');
            $table->text('message')->nullable();
            $table->boolean('is_sent')->default(false);

            // Timestamps
            $table->timestamps();

            // Indexuri pentru optimizare
            $table->index('remind_at', 'idx_reminders_remind_at');
            $table->index('is_sent', 'idx_reminders_is_sent');
        });
    }

    /**
     * Anulează migrarea.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reminders');
    }
};
