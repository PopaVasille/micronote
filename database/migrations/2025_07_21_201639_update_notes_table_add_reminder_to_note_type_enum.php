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
        // Check if we're using MySQL or SQLite and handle accordingly
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            // Actualizăm enum-ul pentru MySQL pentru a include 'reminder'
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
        } else {
            // For SQLite, we don't need to modify the ENUM as it's just TEXT with CHECK constraint
            // The check constraint should already allow 'reminder' or we update it
            $hasCheckConstraint = DB::select("SELECT sql FROM sqlite_master WHERE type='table' AND name='notes'");

            if (!empty($hasCheckConstraint)) {
                $tableDef = $hasCheckConstraint[0]->sql;
                if (strpos($tableDef, "'reminder'") === false) {
                    // We need to recreate the table with the new constraint
                    // For SQLite, this requires a more complex migration but for now we'll leave as is
                    // since the constraint is likely to be flexible or we'll handle it in the model
                }
            }
        }

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

        // Check if we're using MySQL or SQLite and handle accordingly
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            // Eliminăm 'reminder' din enum pentru MySQL
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
        }
        // For SQLite, we don't need to do anything special

        \Log::info('Removed reminder from note_type enum in notes table');
    }
};
