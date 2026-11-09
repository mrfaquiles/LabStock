<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Equipamento pode ser cadastrado "sem local definido" (a baixa já aceita isso).
 * Agora o cadastro gera a entrada inicial, então a entrada também precisa aceitar local vazio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entrada_equipamentos', function (Blueprint $table) {
            $table->unsignedBigInteger('idlaboratorio')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Mantém nulo: entradas "sem local" já registradas impediriam voltar
    }
};
