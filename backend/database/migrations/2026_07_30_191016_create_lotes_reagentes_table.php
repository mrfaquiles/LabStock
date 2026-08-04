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
    Schema::create('lotes_reagentes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('item_id')->constrained('itens')->onDelete('cascade');
        $table->string('numero_lote', 50);
        $table->date('data_validade');
        $table->decimal('quantidade_atual', 10, 2); // Saldo fracionado (ml, gramas, etc.)
        $table->string('local_armazenamento', 100);  // Ex: "Armário 1"
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lotes_reagentes');
    }
};
