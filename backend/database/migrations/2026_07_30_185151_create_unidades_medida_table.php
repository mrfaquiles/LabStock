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
    Schema::create('unidades_medida', function (Blueprint $table) {
        $table->id();
        $table->string('sigla', 10)->unique(); // Ex: g, ml, L, un
        $table->string('descricao', 50);       // Ex: Gramas, Mililitros, Litros, Unidade
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidades_medida');
    }
};
