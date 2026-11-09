<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nome e CATMAT passam a ser únicos em cada cadastro (reagentes, vidrarias e equipamentos).
 *
 * Registros já repetidos: com o mesmo nome (e, em reagentes, a mesma unidade) são o mesmo item,
 * então as movimentações e o estoque vão para o mais antigo e o outro sai, deixando o registro
 * do que foi apagado em historico_alteracoes. O que não dá para juntar recebe um sufixo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->mesclar('reagentes', 'idreagente', ['entrada_reagentes', 'saida_reagentes', 'pedidos_compra'], 'idunidademedida');
        $this->mesclar('vidrarias', 'idvidraria', ['entrada_vidrarias', 'saida_vidrarias', 'pedidos_compra']);
        $this->mesclar('equipamentos', 'idequipamento', ['entrada_equipamentos', 'saida_equipamentos', 'transferencias_equipamento', 'equipamento_patrimonios']);

        foreach (['reagentes' => 'idreagente', 'vidrarias' => 'idvidraria', 'equipamentos' => 'idequipamento'] as $tabela => $chave) {
            $this->separarRepetidos($tabela, $chave, 'nome');
            $this->separarRepetidos($tabela, $chave, 'catmat');

            Schema::table($tabela, function (Blueprint $table) {
                $table->unique('nome');
                $table->unique('catmat');
            });
        }
    }

    public function down(): void
    {
        foreach (['reagentes', 'vidrarias', 'equipamentos'] as $tabela) {
            Schema::table($tabela, function (Blueprint $table) {
                $table->dropUnique(['nome']);
                $table->dropUnique(['catmat']);
            });
        }
    }

    private function mesclar(string $tabela, string $chave, array $dependentes, ?string $mesmoCampo = null): void
    {
        $nomes = DB::table($tabela)->select('nome')->groupBy('nome')->havingRaw('COUNT(*) > 1')->pluck('nome');

        foreach ($nomes as $nome) {
            $registros = DB::table($tabela)->where('nome', $nome)->orderBy($chave)->get();
            $principal = $registros->shift();

            foreach ($registros as $repetido) {
                if ($mesmoCampo && $repetido->{$mesmoCampo} !== $principal->{$mesmoCampo}) {
                    continue; // unidade diferente: não dá para somar (recebe um sufixo depois)
                }

                foreach ($dependentes as $dependente) {
                    DB::table($dependente)->where($chave, $repetido->{$chave})->update([$chave => $principal->{$chave}]);
                }
                if ($tabela === 'equipamentos') {
                    $this->juntarLocais($principal->idequipamento, $repetido->idequipamento);
                }

                DB::table($tabela)->where($chave, $principal->{$chave})->increment('quantidade', $repetido->quantidade);
                if (!empty($repetido->controlado)) {
                    DB::table($tabela)->where($chave, $principal->{$chave})->update(['controlado' => true]);
                }

                DB::table('historico_alteracoes')->insert([
                    'entidade' => $tabela,
                    'registro_id' => $repetido->{$chave},
                    'acao' => 'excluido',
                    'alteracoes' => json_encode(collect((array) $repetido)->except([$chave, 'created_at', 'updated_at'])
                        ->map(fn ($valor) => ['antes' => $valor, 'depois' => null])
                        ->put('mesclado_em', ['antes' => null, 'depois' => $principal->{$chave}])->all()),
                    'idusuario' => null,
                    'created_at' => now(),
                ]);
                DB::table($tabela)->where($chave, $repetido->{$chave})->delete();
            }
        }
    }

    private function juntarLocais(int $principal, int $repetido): void
    {
        foreach (DB::table('equipamento_locais')->where('idequipamento', $repetido)->get() as $local) {
            $mesmoLocal = DB::table('equipamento_locais')->where('idequipamento', $principal)
                ->where(fn ($q) => $local->idlaboratorio === null
                    ? $q->whereNull('idlaboratorio')
                    : $q->where('idlaboratorio', $local->idlaboratorio));

            if ($mesmoLocal->exists()) {
                $mesmoLocal->increment('quantidade', $local->quantidade);
                DB::table('equipamento_locais')->where('id', $local->id)->delete();
            } else {
                DB::table('equipamento_locais')->where('id', $local->id)->update(['idequipamento' => $principal]);
            }
        }
    }

    private function separarRepetidos(string $tabela, string $chave, string $campo): void
    {
        $valores = DB::table($tabela)->whereNotNull($campo)->select($campo)->groupBy($campo)->havingRaw('COUNT(*) > 1')->pluck($campo);

        foreach ($valores as $valor) {
            $registros = DB::table($tabela)->where($campo, $valor)->orderBy($chave)->get();
            $registros->shift();
            foreach ($registros as $repetido) {
                DB::table($tabela)->where($chave, $repetido->{$chave})
                    ->update([$campo => mb_substr($repetido->{$campo}, 0, 40) . ' (' . $repetido->{$chave} . ')']);
            }
        }
    }
};
