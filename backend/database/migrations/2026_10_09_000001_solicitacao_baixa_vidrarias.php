<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Baixa de vidraria passa a ser uma solicitação: o usuário pede com motivo e
     * justificativa (coluna observacao) e o estoque só diminui quando um admin aprova.
     */
    public function up(): void
    {
        Schema::table('saida_vidrarias', function (Blueprint $table) {
            // Baixas registradas antes deste fluxo já foram aplicadas ao estoque
            $table->enum('status', ['pendente', 'aprovada', 'recusada'])->default('aprovada')->after('quantidade');
            $table->string('motivo', 30)->nullable()->after('status');
            $table->unsignedBigInteger('idaprovador')->nullable()->after('idusuario');
            $table->dateTime('data_decisao')->nullable()->after('data');
            $table->string('motivo_recusa')->nullable()->after('observacao');

            $table->foreign('idaprovador')->references('id')->on('users');
            $table->index('status');
        });

        Schema::table('saida_vidrarias', function (Blueprint $table) {
            $table->unsignedBigInteger('idlaboratorio')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('saida_vidrarias', function (Blueprint $table) {
            $table->dropForeign(['idaprovador']);
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'motivo', 'idaprovador', 'data_decisao', 'motivo_recusa']);
        });
    }
};
