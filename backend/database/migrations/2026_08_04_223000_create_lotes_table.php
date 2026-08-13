<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('reagente_id');
            $table->string('numero_lote');
            $table->decimal('quantidade_atual', 10, 2);
            $table->date('data_validade');
            $table->timestamps();

            // Chave estrangeira ligada à tabela reagentes
            $table->foreign('reagente_id')->references('id')->on('reagentes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};