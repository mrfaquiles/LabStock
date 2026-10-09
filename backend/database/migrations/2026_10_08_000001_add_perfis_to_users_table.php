<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perfis de acesso do LabStock:
     *  - admin:    acesso total, inclusive gestão de usuários
     *  - tecnico:  cadastra e movimenta reagentes, vidrarias e equipamentos
     *  - consulta: apenas visualiza estoque e emite relatórios
     */
    public function up(): void
    {
        // Etapa 1: amplia o enum para aceitar os valores antigos e os novos
        Schema::table('users', function (Blueprint $table) {
            $table->enum('tipo', ['admin', 'user', 'tecnico', 'consulta'])->default('consulta')->change();
        });

        // Usuários comuns existentes passam a ser técnicos (mantêm o acesso que já tinham)
        DB::table('users')->where('tipo', 'user')->update(['tipo' => 'tecnico']);

        // Etapa 2: remove o valor antigo
        Schema::table('users', function (Blueprint $table) {
            $table->enum('tipo', ['admin', 'tecnico', 'consulta'])->default('consulta')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('tipo', ['admin', 'user', 'tecnico', 'consulta'])->change();
        });

        DB::table('users')->whereIn('tipo', ['tecnico', 'consulta'])->update(['tipo' => 'user']);

        Schema::table('users', function (Blueprint $table) {
            $table->enum('tipo', ['admin', 'user'])->change();
        });
    }
};
