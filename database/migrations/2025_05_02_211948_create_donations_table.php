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
        Schema::create('donations', function (Blueprint $table) {
            // Cheia primară
            $table->id();

            // Foreign key către tabela users (opțional - donația poate fi anonimă)
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            // Câmpuri principale pentru donații
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->enum('gateway', ['stripe', 'paypal']);
            $table->string('transaction_id', 255)->nullable();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');

            // Metadate suplimentare în format JSON
            $table->json('metadata')->nullable();

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Anulează migrarea.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('donations');
    }
};
