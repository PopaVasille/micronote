<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizăm enum-ul pentru a include 'reminder'
        DB::statement("ALTER TABLE notes MODIFY COLUMN note_type ENUM(
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
        ) DEFAULT 'simple'");

        // Log pentru confirmare
        \Log::info('Added reminder to note_type enum in notes table');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Verificăm dacă există înregistrări cu tipul 'reminder'
        $reminderNotesCount = DB::table('notes')->where('note_type', 'reminder')->count();

        if ($reminderNotesCount > 0) {
            // Convertim notițele de tip 'reminder' în 'task' înainte de a elimina din enum
            DB::table('notes')
                ->where('note_type', 'reminder')
                ->update(['note_type' => 'task']);

            \Log::info("Converted {$reminderNotesCount} reminder notes to task type before rollback");
        }

        // Eliminăm 'reminder' din enum
        DB::statement("ALTER TABLE notes MODIFY COLUMN note_type ENUM(
            'simple',
            'shopping_list',
            'task',
            'idea',
            'event',
            'contact',
            'recipe',
            'bookmark',
            'measurement'
        ) DEFAULT 'simple'");

        \Log::info('Removed reminder from note_type enum in notes table');
    }
};
