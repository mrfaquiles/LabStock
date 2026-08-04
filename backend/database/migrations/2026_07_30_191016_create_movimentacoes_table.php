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
        Schema::create('movimentacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('itens');
            $table->foreignId('user_id')->constrained('users'); // Quem retirou/solicitou
            $table->enum('tipo_movimentacao', ['baixa_quebra', 'emprestimo', 'consumo_reagente']);
            $table->decimal('quantidade_movimentada', 10, 2)->nullable(); // Usado para reagentes
            $table->text('motivo_ou_destino'); // Destino do equipamento ou motivo da quebra/baixa
            $table->enum('status_aprovacao', ['pendente', 'aprovado', 'rejeitado'])->default('pendente'); // Para vidrarias
            $table->date('data_devolucao')->nullable(); // Para controle de empréstimos
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimentacoes');
    }
};
