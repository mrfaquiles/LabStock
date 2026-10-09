<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A entrada de reagentes registra a chegada de um lote: todas as unidades
     * do lote compartilham a mesma data de validade.
     */
    public function up(): void
    {
        Schema::table('entrada_reagentes', function (Blueprint $table) {
            $table->decimal('quantidade', 10, 3)->default(0)->after('idusuario');
            $table->string('lote')->nullable()->after('quantidade');
            $table->date('data_validade')->nullable()->after('lote');
            $table->date('data')->nullable()->after('data_validade');
            $table->string('observacao')->nullable()->after('data');
        });

        // Reagentes cadastrados direto pela tela não têm entrada vinculada
        Schema::table('saida_reagentes', function (Blueprint $table) {
            $table->unsignedBigInteger('identrada')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('saida_reagentes', function (Blueprint $table) {
            $table->unsignedBigInteger('identrada')->nullable(false)->change();
        });

        Schema::table('entrada_reagentes', function (Blueprint $table) {
            $table->dropColumn(['quantidade', 'lote', 'data_validade', 'data', 'observacao']);
        });
    }
};
