<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unidades (campus/sedes), estoque de equipamentos por laboratório,
     * transferências entre locais e histórico de alterações.
     */
    public function up(): void
    {
        // Unidades da instituição (ex.: Unidade 1, Unidade 2)
        Schema::create('unidades', function (Blueprint $table) {
            $table->id('idunidade');
            $table->string('nome');
            $table->string('endereco')->nullable();
            $table->timestamps();
        });

        // Cada laboratório pertence a uma unidade
        Schema::table('laboratorios', function (Blueprint $table) {
            $table->unsignedBigInteger('idunidade')->nullable()->after('idlaboratorio');
            $table->foreign('idunidade')->references('idunidade')->on('unidades');
        });

        // Quantas unidades de cada equipamento há em cada laboratório.
        // idlaboratorio nulo = "sem local definido" (equipamentos cadastrados antes deste controle)
        Schema::create('equipamento_locais', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('idequipamento');
            $table->unsignedBigInteger('idlaboratorio')->nullable();
            $table->unsignedInteger('quantidade')->default(0);
            $table->timestamps();

            $table->foreign('idequipamento')->references('idequipamento')->on('equipamentos')->cascadeOnDelete();
            $table->foreign('idlaboratorio')->references('idlaboratorio')->on('laboratorios');
            $table->unique(['idequipamento', 'idlaboratorio']);
        });

        // Transferências de equipamento entre laboratórios (inclusive de unidades diferentes)
        Schema::create('transferencias_equipamento', function (Blueprint $table) {
            $table->id('idtransferencia');
            $table->unsignedBigInteger('idequipamento');
            $table->unsignedBigInteger('idorigem')->nullable(); // nulo = sem local definido
            $table->unsignedBigInteger('iddestino');
            $table->unsignedInteger('quantidade');
            $table->unsignedBigInteger('idusuario');
            $table->dateTime('data');
            $table->string('observacao')->nullable();
            $table->timestamps();

            $table->foreign('idequipamento')->references('idequipamento')->on('equipamentos');
            $table->foreign('idorigem')->references('idlaboratorio')->on('laboratorios');
            $table->foreign('iddestino')->references('idlaboratorio')->on('laboratorios');
            $table->foreign('idusuario')->references('id')->on('users');
        });

        // Histórico de edições (quem alterou, quando, valor antes e depois de cada campo)
        Schema::create('historico_alteracoes', function (Blueprint $table) {
            $table->id();
            $table->string('entidade', 40);          // ex.: equipamentos
            $table->unsignedBigInteger('registro_id');
            $table->enum('acao', ['criado', 'alterado', 'excluido']);
            $table->json('alteracoes')->nullable();  // {campo: {antes, depois}}
            $table->unsignedBigInteger('idusuario')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['entidade', 'registro_id']);
            $table->foreign('idusuario')->references('id')->on('users')->nullOnDelete();
        });

        // Baixa de equipamento que ainda está "sem local definido"
        Schema::table('saida_equipamentos', function (Blueprint $table) {
            $table->unsignedBigInteger('idlaboratorio')->nullable()->change();
        });

        // Equipamentos já cadastrados: a quantidade atual fica "sem local definido",
        // para o laboratório transferir para o local certo pela tela
        $agora = now();
        foreach (DB::table('equipamentos')->where('quantidade', '>', 0)->get() as $equipamento) {
            DB::table('equipamento_locais')->insert([
                'idequipamento' => $equipamento->idequipamento,
                'idlaboratorio' => null,
                'quantidade' => $equipamento->quantidade,
                'created_at' => $agora,
                'updated_at' => $agora,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('historico_alteracoes');
        Schema::dropIfExists('transferencias_equipamento');
        Schema::dropIfExists('equipamento_locais');
        Schema::table('laboratorios', function (Blueprint $table) {
            $table->dropForeign(['idunidade']);
            $table->dropColumn('idunidade');
        });
        Schema::dropIfExists('unidades');
    }
};
