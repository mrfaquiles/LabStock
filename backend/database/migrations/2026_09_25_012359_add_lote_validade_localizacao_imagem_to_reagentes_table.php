<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reagentes', function (Blueprint $table) {
            $table->string('lote')->after('quantidade')->nullable();
            $table->date('data_validade')->after('lote')->nullable();
            $table->string('localizacao')->after('data_validade')->nullable();
            $table->string('imagem')->after('localizacao')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('reagentes', function (Blueprint $table) {
            $table->dropColumn(['lote', 'data_validade', 'localizacao', 'imagem']);
        });
    }
};