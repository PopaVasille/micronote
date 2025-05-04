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
        Schema::create('incoming_messages', function (Blueprint $table) {
            // Cheia primară
            $table->id();

            // Cheie străină către tabelul users (cu opțiunea de NULL în caz că un utilizator este șters)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');

            // Câmpuri pentru informații despre sursă
            $table->enum('source_type', ['telegram', 'whatsapp'])->default('telegram');
            $table->enum('source_platform', ['web', 'android', 'ios'])->default('web');
            $table->string('sender_identifier', 255);

            // Conținutul mesajului
            $table->text('message_content');

            // Câmpuri pentru procesare
            $table->string('ai_tag', 50)->nullable();
            $table->boolean('is_processed')->default(false);
            $table->timestamp('processed_at')->nullable();

            // Metadate adiționale (format JSON pentru flexibilitate)
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamp('created_at')->useCurrent();

            // Indexuri
            $table->index('source_type');
            $table->index('is_processed');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incoming_messages');
    }
};
