<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saida_vidrarias', function (Blueprint $table) {
            $table->id('idsaidavidraria');
            $table->unsignedBigInteger('idvidraria');
            $table->unsignedBigInteger('idlaboratorio');
            $table->unsignedBigInteger('idusuario');
            $table->integer('quantidade');
            $table->date('data');
            $table->string('observacao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saida_vidrarias');
    }
};
