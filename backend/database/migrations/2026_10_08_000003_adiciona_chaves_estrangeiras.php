<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * As migrations originais usavam unsignedBigInteger()->constrained(), que não cria
     * a chave estrangeira. Aqui elas são criadas de fato. Sem cascade: um item com
     * movimentações não pode ser apagado, preservando o histórico.
     *
     * [tabela => [coluna => [tabela referenciada, coluna referenciada]]]
     */
    private array $chaves = [
        'reagentes' => [
            'idunidademedida' => ['unidade_medidas', 'idunidademedida'],
        ],
        'entrada_reagentes' => [
            'idreagente' => ['reagentes', 'idreagente'],
            'idlaboratorio' => ['laboratorios', 'idlaboratorio'],
            'idusuario' => ['users', 'id'],
        ],
        'saida_reagentes' => [
            'idreagente' => ['reagentes', 'idreagente'],
            'identrada' => ['entrada_reagentes', 'identradareagente'],
            'idusuario' => ['users', 'id'],
        ],
        'entrada_vidrarias' => [
            'idvidraria' => ['vidrarias', 'idvidraria'],
            'idlaboratorio' => ['laboratorios', 'idlaboratorio'],
            'idusuario' => ['users', 'id'],
        ],
        'saida_vidrarias' => [
            'idvidraria' => ['vidrarias', 'idvidraria'],
            'idlaboratorio' => ['laboratorios', 'idlaboratorio'],
            'idusuario' => ['users', 'id'],
        ],
        'entrada_equipamentos' => [
            'idequipamento' => ['equipamentos', 'idequipamento'],
            'idlaboratorio' => ['laboratorios', 'idlaboratorio'],
            'idusuario' => ['users', 'id'],
        ],
        'saida_equipamentos' => [
            'idequipamento' => ['equipamentos', 'idequipamento'],
            'idlaboratorio' => ['laboratorios', 'idlaboratorio'],
            'idusuario' => ['users', 'id'],
        ],
    ];

    public function up(): void
    {
        $this->verificarRegistrosOrfaos();

        foreach ($this->chaves as $tabela => $colunas) {
            Schema::table($tabela, function (Blueprint $table) use ($colunas) {
                foreach ($colunas as $coluna => [$refTabela, $refColuna]) {
                    $table->foreign($coluna)->references($refColuna)->on($refTabela);
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->chaves as $tabela => $colunas) {
            Schema::table($tabela, function (Blueprint $table) use ($colunas) {
                foreach (array_keys($colunas) as $coluna) {
                    $table->dropForeign([$coluna]);
                }
            });
        }
    }

    /**
     * Não apaga dados: se houver registros apontando para algo inexistente,
     * interrompe a migration e informa onde estão, para correção manual.
     */
    private function verificarRegistrosOrfaos(): void
    {
        $problemas = [];

        foreach ($this->chaves as $tabela => $colunas) {
            foreach ($colunas as $coluna => [$refTabela, $refColuna]) {
                $orfaos = DB::table($tabela)
                    ->whereNotNull($coluna)
                    ->whereNotIn($coluna, DB::table($refTabela)->select($refColuna))
                    ->count();

                if ($orfaos > 0) {
                    $problemas[] = "{$tabela}.{$coluna}: {$orfaos} registro(s) sem correspondente em {$refTabela}";
                }
            }
        }

        if ($problemas) {
            throw new RuntimeException(
                "Corrija os registros órfãos antes de criar as chaves estrangeiras:\n - " . implode("\n - ", $problemas)
            );
        }
    }
};
