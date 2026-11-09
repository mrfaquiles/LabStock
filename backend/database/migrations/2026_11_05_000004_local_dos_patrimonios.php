<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cada patrimônio passa a saber em qual laboratório está,
     * para a transferência e a baixa escolherem a unidade certa.
     */
    public function up(): void
    {
        Schema::table('equipamento_patrimonios', function (Blueprint $table) {
            $table->unsignedBigInteger('idlaboratorio')->nullable()->after('numero'); // nulo = sem local definido
            $table->foreign('idlaboratorio')->references('idlaboratorio')->on('laboratorios');
        });

        // 1) Local da entrada que trouxe a unidade
        foreach (DB::table('equipamento_patrimonios')->whereNotNull('identrada')->get() as $p) {
            $lab = DB::table('entrada_equipamentos')->where('identradaequipamento', $p->identrada)->value('idlaboratorio');
            DB::table('equipamento_patrimonios')->where('idpatrimonio', $p->idpatrimonio)->update(['idlaboratorio' => $lab]);
        }

        // 2) Ajusta para bater com as quantidades de cada local (as transferências antigas não diziam qual unidade foi)
        foreach (DB::table('equipamento_patrimonios')->whereNull('baixado_em')->pluck('idequipamento')->unique() as $idequipamento) {
            $locais = DB::table('equipamento_locais')->where('idequipamento', $idequipamento)->where('quantidade', '>', 0)
                ->get()->mapWithKeys(fn ($l) => [($l->idlaboratorio ?? 'sem') => (int) $l->quantidade])->all();
            $patrimonios = DB::table('equipamento_patrimonios')->where('idequipamento', $idequipamento)
                ->whereNull('baixado_em')->orderBy('idpatrimonio')->get();

            $usados = [];
            $sobrando = [];
            foreach ($patrimonios as $p) {
                $chave = $p->idlaboratorio ?? 'sem';
                if (($usados[$chave] ?? 0) < ($locais[$chave] ?? 0)) {
                    $usados[$chave] = ($usados[$chave] ?? 0) + 1;
                } else {
                    $sobrando[] = $p;
                }
            }
            foreach ($sobrando as $p) {
                $destino = collect($locais)->keys()->first(fn ($chave) => ($usados[$chave] ?? 0) < $locais[$chave]);
                if ($destino === null) {
                    break;
                }
                $usados[$destino] = ($usados[$destino] ?? 0) + 1;
                DB::table('equipamento_patrimonios')->where('idpatrimonio', $p->idpatrimonio)
                    ->update(['idlaboratorio' => $destino === 'sem' ? null : $destino]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('equipamento_patrimonios', function (Blueprint $table) {
            $table->dropForeign(['idlaboratorio']);
            $table->dropColumn('idlaboratorio');
        });
    }
};
