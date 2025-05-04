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
        Schema::create('tags', function (Blueprint $table) {
            // Cheia primară
            $table->id();

            // Câmpuri de bază
            $table->string('name', 50);
            $table->string('color', 20)->default('#3498db');
            $table->boolean('is_system')->default(false);

            // Cheie străină către tabelul users (optional - pentru tag-uri personalizate)
            // Null pentru tag-uri de sistem care nu aparțin unui utilizator specific
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');

            // Timestamps
            $table->timestamps();

            // Chei unice
            $table->unique(['name', 'user_id'], 'unique_tag_per_user');

            // Indexuri
            $table->index('is_system');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
    }
};
