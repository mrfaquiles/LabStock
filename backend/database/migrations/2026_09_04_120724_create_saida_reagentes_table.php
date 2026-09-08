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
        Schema::create('saida_reagentes', function (Blueprint $table) {
            $table->id('idsaidareagente');
            $table->unsignedBigInteger('idreagente')->constrained('reagentes');
            $table->unsignedBigInteger('identrada')->constrained('entrada_reagentes');
            $table->unsignedBigInteger('idusuario')->constrained('usuarios');
            $table->decimal('quantidade', 10, 3);
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
        Schema::dropIfExists('saida_reagentes');
    }
};
