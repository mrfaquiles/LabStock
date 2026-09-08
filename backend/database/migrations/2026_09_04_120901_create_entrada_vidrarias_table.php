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
        Schema::create('entrada_vidrarias', function (Blueprint $table) {
            $table->id('identravidraria');
            $table->unsignedBigInteger('idvidraria')->constrained('vidrarias');
            $table->unsignedBigInteger('idlaboratorio')->constrained('laboratorios');
            $table->unsignedBigInteger('idusuario')->constrained('usuarios');
            $table->integer('quantidade');
            $table->datetime('data');
            $table->string('observacao')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrada_vidrarias');
    }
};
