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
        Schema::create('equipamentos', function (Blueprint $table) {
            $table->id('idequipamento');
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->integer('quantidade')->default(0);
            $table->string('catmat', 50)->nullable();
            // Campos novos adicionados
            $table->string('patrimonio')->nullable();
            $table->string('status')->default('Operacional');
            $table->string('localizacao')->nullable();
            
            $table->tinyInteger('ativo')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipamentos');
    }
};