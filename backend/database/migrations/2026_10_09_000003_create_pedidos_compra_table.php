<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Acompanhamento dos pedidos de compra feitos pelo laboratório
     * (do pedido até a entrega pela licitação).
     */
    public function up(): void
    {
        Schema::create('pedidos_compra', function (Blueprint $table) {
            $table->id('idpedido');
            // Um pedido é de um reagente OU de uma vidraria
            $table->unsignedBigInteger('idreagente')->nullable();
            $table->unsignedBigInteger('idvidraria')->nullable();
            $table->unsignedBigInteger('idusuario');
            // Na unidade base do item (kg, L, un)
            $table->decimal('quantidade', 10, 3);
            $table->enum('status', ['solicitado', 'em_licitacao', 'aguardando_entrega', 'recebido', 'cancelado'])
                ->default('solicitado');
            $table->date('data_pedido');
            $table->date('previsao_entrega')->nullable();
            $table->date('data_recebimento')->nullable();
            $table->string('observacao')->nullable();
            $table->timestamps();

            $table->foreign('idreagente')->references('idreagente')->on('reagentes');
            $table->foreign('idvidraria')->references('idvidraria')->on('vidrarias');
            $table->foreign('idusuario')->references('id')->on('users');
            $table->index('status');
        });

        // Ao receber um pedido de vidraria, a entrada é registrada sem exigir laboratório
        Schema::table('entrada_vidrarias', function (Blueprint $table) {
            $table->unsignedBigInteger('idlaboratorio')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos_compra');
    }
};
