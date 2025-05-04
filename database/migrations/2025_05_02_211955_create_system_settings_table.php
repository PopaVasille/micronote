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
        Schema::create('system_settings', function (Blueprint $table) {
            // Folosim key_name ca și cheie primară (string)
            $table->string('key_name', 100)->primary();

            // Câmpuri pentru valorile setărilor
            $table->text('value')->nullable();
            $table->json('json_value')->nullable();

            // Doar timestamp de actualizare (nu avem nevoie de created_at)
            $table->timestamp('updated_at')
                ->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
        });

        // Inserăm setările inițiale din sistem
        DB::table('system_settings')->insert([
            [
                'key_name' => 'app_version',
                'value' => '1.0.0',
                'json_value' => null
            ],
            [
                'key_name' => 'monthly_free_limit',
                'value' => '200',
                'json_value' => null
            ],
            [
                'key_name' => 'plus_monthly_price_eur',
                'value' => '2.00',
                'json_value' => null
            ],
            [
                'key_name' => 'ai_suggestion_limit_plus',
                'value' => '30',
                'json_value' => null
            ],
            [
                'key_name' => 'regex_patterns',
                'value' => null,
                'json_value' => json_encode([
                    'task' => '\\b(task|todo|reminder|de (facut|făcut)|trebuie să|nu uita|amintește)\\b',
                    'idea' => '\\b(idee|idea|concept|brainstorm|gandesc la|gândesc la|ar fi (bine|misto))\\b',
                    'shopping' => '\\b(cumpără|cumparaturi|cumpărături|lista|magazin)\\b',
                    'event' => '\\b(rezervare|întâlnire|webinar|eveniment)\\b',
                    'contact' => '\\b(sun[aă] pe|trimite email lui|contacteaz[aă])\\b',
                    'recipe' => '\\b(rețetă|ingrediente|preparare)\\b',
                    'bookmark' => '\\b(https?://|www\\.|link|url)\\b',
                    'measurement' => '\\b(\\d+[.,]?\\d*\\s*(m|kg|l|cm))\\b'
                ])
            ]
        ]);
    }

    /**
     * Anulează migrarea.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('system_settings');
    }
};
