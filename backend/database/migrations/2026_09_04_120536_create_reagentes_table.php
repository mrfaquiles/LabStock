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
        Schema::create('reagentes', function (Blueprint $table) {
            $table->id('idreagente');
            $table->string('nome');
            $table->text('descricao')->nullable(); 
            $table->unsignedBigInteger('idunidademedida')->constrained('unidade_medidas');
            $table->decimal('quantidade', 10, 3)->default(0);
            $table->string('catmat', 50)->nullable();
            $table->tinyInteger('ativo')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reagentes');
    }
};
