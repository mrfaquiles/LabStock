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
    Schema::create('itens', function (Blueprint $table) {
        $table->id();
        $table->string('codigo_cat', 50)->unique(); // Código CATMAT oficial
        $table->string('nome_curto', 100);          // Nome para exibição rápida na tabela
        $table->text('descricao_longa');            // Descrição detalhada para a Tooltip
        $table->enum('categoria', ['Vidraria', 'Equipamento', 'Reagente']); // As 3 categorias
        $table->foreignId('unidade_medida_id')->constrained('unidades_medida'); // Unidade padrão
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('itens');
    }
};
