<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada unidade de um equipamento tem o seu número de patrimônio.
     * Cadastrar um patrimônio soma 1 unidade ao estoque; dar baixa nele tira 1.
     * O número nunca é reaproveitado, mesmo depois da baixa.
     */
    public function up(): void
    {
        Schema::create('equipamento_patrimonios', function (Blueprint $table) {
            $table->id('idpatrimonio');
            $table->unsignedBigInteger('idequipamento');
            $table->string('numero', 100)->unique();
            $table->unsignedBigInteger('identrada')->nullable(); // entrada que trouxe a unidade
            $table->unsignedBigInteger('idsaida')->nullable();   // baixa que tirou a unidade
            $table->timestamp('baixado_em')->nullable();
            $table->timestamps();

            $table->foreign('idequipamento')->references('idequipamento')->on('equipamentos');
            $table->foreign('identrada')->references('identradaequipamento')->on('entrada_equipamentos');
            $table->foreign('idsaida')->references('idsaidaequipamento')->on('saida_equipamentos');
            $table->index(['idequipamento', 'baixado_em']);
        });

        // O número que estava no cadastro vira o patrimônio de uma das unidades
        // (números repetidos entre equipamentos ficam só no primeiro)
        $vistos = [];
        foreach (DB::table('equipamentos')->whereNotNull('patrimonio')->where('patrimonio', '<>', '')->orderBy('idequipamento')->get() as $e) {
            $numero = trim($e->patrimonio);
            if (isset($vistos[mb_strtolower($numero)]) || $e->quantidade < 1) {
                continue;
            }
            $vistos[mb_strtolower($numero)] = true;
            DB::table('equipamento_patrimonios')->insert([
                'idequipamento' => $e->idequipamento,
                'numero' => $numero,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('equipamentos', function (Blueprint $table) {
            $table->dropColumn('patrimonio');
        });
    }

    public function down(): void
    {
        Schema::table('equipamentos', function (Blueprint $table) {
            $table->string('patrimonio')->nullable()->after('catmat');
        });

        foreach (DB::table('equipamento_patrimonios')->whereNull('baixado_em')->orderBy('idpatrimonio')->get()->groupBy('idequipamento') as $id => $lista) {
            DB::table('equipamentos')->where('idequipamento', $id)->update(['patrimonio' => $lista->first()->numero]);
        }

        Schema::dropIfExists('equipamento_patrimonios');
    }
};
