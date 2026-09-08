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
        Schema::create('saida_equipamentos', function (Blueprint $table) {
            $table->id('idsaidaequipamento');
            $table->unsignedBigInteger('idequipamento')->constrained('equipamentos');
            $table->unsignedBigInteger('idlaboratorio')->constrained('laboratorios');
            $table->unsignedBigInteger('idusuario')->constrained('usuarios');
            $table->integer('quantidade');
            $table->date('data');
            $table->string('observacao')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saida_equipamentos');
    }
};
