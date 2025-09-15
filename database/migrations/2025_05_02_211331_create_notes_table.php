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
        Schema::create('notes', function (Blueprint $table) {
            // Cheia primară
            $table->id();
            $table->uuid('uuid')->unique();

            // Chei străine
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('incoming_message_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('parent_id')->nullable()->constrained('notes')->onDelete('cascade');

            // Câmpuri de bază
            $table->string('title', 255)->nullable();
            $table->text('content');
            $table->enum('note_type', [
                'simple',
                'shopping_list',
                'task',
                'idea',
                'reminder',
                'event',
                'contact',
                'recipe',
                'bookmark',
                'measurement'
            ])->default('simple');

            // Stare și prioritate
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_favorite')->default(false);
            $table->tinyInteger('priority')->default(0);

            // Metadate și versionare
            $table->json('metadata')->nullable();
            $table->integer('version')->default(1);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            // Timestamps
            $table->timestamps();

            // Indexuri
            $table->index('user_id');
            $table->index('is_completed');
            $table->index('is_favorite');
            $table->index('note_type');
            $table->index('parent_id');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
