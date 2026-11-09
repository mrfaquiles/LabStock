<?php

namespace Database\Seeders;

use App\Models\entrada_equipamento;
use App\Models\entrada_reagente;
use App\Models\entrada_vidraria;
use App\Models\equipamento;
use App\Models\equipamento_local;
use App\Models\equipamento_patrimonio;
use App\Models\laboratorio;
use App\Models\pedido_compra;
use App\Models\reagente;
use App\Models\saida_reagente;
use App\Models\saida_vidraria;
use App\Models\unidade;
use App\Models\unidade_medida;
use App\Models\User;
use App\Models\vidraria;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Dados de demonstração do LabStock: reagentes (alguns controlados e alguns
 * vencidos), um ano de consumo para a previsão de compras, vidrarias com baixas,
 * equipamentos por local e pedidos de compra.
 *
 * Só acrescenta dados (não apaga nada) e não roda duas vezes.
 * Uso: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    use WithoutModelEvents;

    // Senha dos usuários de demonstração (troque se for usar o sistema de verdade)
    // Lista e código na Portaria MJSP 204/2022 (com as alterações da Portaria 223/2022)
    private const CLASSIFICACOES = [
        'Hidróxido de Amônio 28%' => 'Lista V - 066',
        'Ácido Clorídrico 37%' => 'Lista IV - 058',
        'Ácido Sulfúrico 98%' => 'Lista IV - 063',
        'Acetona P.A.' => 'Lista II - 032',
        'Éter Etílico P.A.' => 'Lista II - 036',
        'Permanganato de Potássio P.A.' => 'Lista VI - 088',
        'Tolueno P.A.' => 'Lista II - 038',
    ];

    private const SENHA_DEMO = 'labstock@2026';

    private Carbon $hoje;
    private array $usuarios = [];
    private array $labs = [];

    public function run(): void
    {
        if (reagente::where('nome', 'Hidróxido de Amônio 28%')->exists()) {
            $this->command?->warn('Os dados de demonstração já foram inseridos. Nada foi alterado.');
            return;
        }

        $this->hoje = Carbon::today();
        mt_srand(2026); // mesmo resultado a cada execução

        DB::transaction(function () {
            $this->usuarios();
            $this->locais();
            $this->reagentes();
            $this->vidrarias();
            $this->equipamentos();
        });

        $this->command?->info('Dados de demonstração inseridos.');
    }

    private function usuarios(): void
    {
        $lista = [
            'admin' => ['Administrador', 'admin@labstock.local', User::PERFIL_ADMIN],
            'ana' => ['Ana Paula Ribeiro', 'ana.ribeiro@labstock.local', User::PERFIL_TECNICO],
            'juliana' => ['Juliana Martins', 'juliana.martins@labstock.local', User::PERFIL_TECNICO],
            'lucas' => ['Lucas Andrade (monitor)', 'lucas.andrade@labstock.local', User::PERFIL_CONSULTA],
        ];

        foreach ($lista as $chave => [$nome, $email, $tipo]) {
            $this->usuarios[$chave] = User::firstOrCreate(
                ['email' => $email],
                ['nome' => $nome, 'password' => self::SENHA_DEMO, 'tipo' => $tipo, 'ativo' => 1]
            )->id;
        }
    }

    private function locais(): void
    {
        $barra = unidade::firstOrCreate(['nome' => 'IF Campus Barra']);
        $sofoco = unidade::firstOrCreate(['nome' => 'Campus Sofocó']);

        $lista = [
            'geral' => [$barra, 'Laboratório de Química Geral', 'Aulas práticas dos cursos técnicos'],
            'analitica' => [$barra, 'Laboratório de Química Analítica', 'Titulações, preparo de soluções e análises'],
            'almox' => [$barra, 'Almoxarifado de Reagentes', 'Armários ventilados e armário trancado para controlados'],
            'solos' => [$sofoco, 'Laboratório de Análise de Solos', 'Análises de solo e água'],
        ];

        foreach ($lista as $chave => [$unidade, $nome, $descricao]) {
            $this->labs[$chave] = laboratorio::firstOrCreate(
                ['nome' => $nome],
                ['idunidade' => $unidade->idunidade, 'descricao' => $descricao]
            )->idlaboratorio;
        }
    }

    private function reagentes(): void
    {
        $kg = unidade_medida::where('sigla', 'kg')->value('idunidademedida');
        $l = unidade_medida::where('sigla', 'L')->value('idunidademedida');

        $aulas = [
            'Aula prática de Química Geral - Turma 1º ano A',
            'Aula prática de Química Geral - Turma 1º ano B',
            'Aula de titulação ácido-base - Química Analítica',
            'Preparo de solução padrão para aula prática',
            'Projeto de iniciação científica - análise de água',
            'Aula de reações químicas - Turma 2º ano',
            'Análise de solo para projeto de extensão',
        ];

        // nome, CAS, catmat, unidade, local, controlado, lotes [meses atrás, qtd, validade em meses a partir de hoje], consumo mensal médio
        $dados = [
            ['Hidróxido de Amônio 28%', '1336-21-6', '358672', $l, 'Almoxarifado - Armário trancado A1', true,
                [[13, 5, 18], [4, 3, 30]], 0.45],
            ['Ácido Clorídrico 37%', '7647-01-0', '360420', $l, 'Almoxarifado - Armário trancado A1', true,
                [[12, 4, 24]], 0.30],
            ['Ácido Sulfúrico 98%', '7664-93-9', '360375', $l, 'Almoxarifado - Armário trancado A2', true,
                [[11, 3, 30]], 0.20],
            ['Acetona P.A.', '67-64-1', '358921', $l, 'Almoxarifado - Armário trancado A2', true,
                [[12, 6, 20]], 0.55],
            ['Éter Etílico P.A.', '60-29-7', '361005', $l, 'Almoxarifado - Armário trancado A3', true,
                [[10, 2, 6]], 0.08],
            ['Permanganato de Potássio P.A.', '7722-64-7', '363110', $kg, 'Almoxarifado - Armário trancado A3', true,
                [[12, 0.5, 36]], 0.015],
            ['Tolueno P.A.', '108-88-3', '362780', $l, 'Almoxarifado - Armário trancado A3', true,
                [[9, 1, 24]], 0.04],
            ['Hidróxido de Sódio em lentilhas', '1310-73-2', '359440', $kg, 'Almoxarifado - Prateleira B2', false,
                [[13, 2, 22], [3, 1, 34]], 0.12],
            ['Cloreto de Sódio P.A.', '7647-14-5', '359811', $kg, 'Almoxarifado - Prateleira B1', false,
                [[12, 3, 40]], 0.10],
            ['Álcool Etílico 96%', '64-17-5', '360001', $l, 'Almoxarifado - Armário de inflamáveis', false,
                [[12, 10, 14], [2, 5, 26]], 0.90],
            ['Ácido Acético Glacial', '64-19-7', '358120', $l, 'Almoxarifado - Armário de ácidos', false,
                [[11, 2, 18]], 0.10],
            ['Fenolftaleína 1% (indicador)', '77-09-8', '362015', $l, 'Lab. Analítica - Bancada 2', false,
                [[14, 0.5, -1]], 0.02],  // lote vencido
            ['Sulfato de Cobre II Pentahidratado', '7758-99-8', '364220', $kg, 'Almoxarifado - Prateleira B3', false,
                [[12, 1, 20]], 0.03],
            ['Nitrato de Prata P.A.', '7761-88-8', '362500', $kg, 'Almoxarifado - Armário escuro C1', false,
                [[12, 0.1, 2]], 0.004],  // vence em 2 meses (alerta)
        ];

        foreach ($dados as [$nome, $cas, $catmat, $unidade, $local, $controlado, $lotes, $mensal]) {
            $primeiro = $lotes[0];
            $reagente = reagente::forceCreate([
                'nome' => $nome,
                'catmat' => $catmat,
                'cas' => $cas,
                'idunidademedida' => $unidade,
                'controlado' => $controlado,
                'classificacao_controle' => self::CLASSIFICACOES[$nome] ?? null,
                'localizacao' => $local,
                'meses_alerta' => 4,
                'quantidade' => 0,
                'ativo' => 1,
                'created_at' => $this->hoje->copy()->subMonths($primeiro[0]),
                'updated_at' => $this->hoje->copy()->subMonths($primeiro[0]),
            ]);

            $total = 0;
            $lotesCriados = [];
            foreach ($lotes as $i => [$mesesAtras, $qtd, $validade]) {
                $data = $this->hoje->copy()->subMonths($mesesAtras)->addDays(mt_rand(0, 5));
                $lote = entrada_reagente::forceCreate([
                    'idreagente' => $reagente->idreagente,
                    'idlaboratorio' => $this->labs['almox'],
                    'idusuario' => $this->usuarios['ana'],
                    'quantidade' => $qtd,
                    'lote' => $this->codigoLote($nome, $i + 1),
                    'data_validade' => $this->hoje->copy()->addMonths($validade)->toDateString(),
                    'data' => $data->toDateString(),
                    'observacao' => $i === 0
                        ? 'Compra via pregão eletrônico - NF ' . mt_rand(10000, 99999)
                        : 'Recebimento do pedido de compra - NF ' . mt_rand(10000, 99999),
                    'created_at' => $data,
                    'updated_at' => $data,
                ]);
                $lote->saldo = $qtd;
                $lotesCriados[$i] = $lote;
                $total += $qtd;
            }

            // Um uso por semana de aula, desde a chegada do primeiro lote
            $dia = $this->hoje->copy()->subMonths($primeiro[0])->addWeek();
            while ($dia->lt($this->hoje)) {
                // Férias: janeiro e julho com pouco uso
                $ferias = in_array($dia->month, [1, 7], true);
                if (!$ferias || mt_rand(0, 3) === 0) {
                    $quantidade = round($mensal / 4 * (0.6 + mt_rand(0, 80) / 100), 4);
                    // Primeiro lote disponível já recebido (FEFO), sem usar lote vencido
                    $lote = collect($lotesCriados)
                        ->filter(fn ($l) => $l->data <= $dia->toDateString()
                            && $l->data_validade >= $dia->toDateString()
                            && $l->saldo >= $quantidade)
                        ->sortBy('data_validade')
                        ->first();

                    if ($lote) {
                        saida_reagente::forceCreate([
                            'idreagente' => $reagente->idreagente,
                            'identrada' => $lote->identradareagente,
                            'idusuario' => mt_rand(0, 1) ? $this->usuarios['ana'] : $this->usuarios['juliana'],
                            'quantidade' => $quantidade,
                            'data' => $dia->toDateString(),
                            'observacao' => $aulas[array_rand($aulas)],
                            'created_at' => $dia->copy()->setTime(mt_rand(8, 17), mt_rand(0, 59)),
                            'updated_at' => $dia->copy()->setTime(mt_rand(8, 17), mt_rand(0, 59)),
                        ]);
                        $lote->saldo -= $quantidade;
                        $total -= $quantidade;
                    }
                }
                $dia->addWeek();
            }

            $reagente->forceFill(['quantidade' => round($total, 6)])->save();
            $this->reagentesCriados[$nome] = $reagente->idreagente;
        }

        // Pedidos de compra em andamento e já recebidos
        $pedidos = [
            ['Acetona P.A.', 6, pedido_compra::EM_LICITACAO, 2, null, 'Pregão 15/2026'],
            ['Álcool Etílico 96%', 10, pedido_compra::AGUARDANDO_ENTREGA, 5, 1, 'Empenho emitido'],
            ['Hidróxido de Amônio 28%', 3, pedido_compra::RECEBIDO, 6, null, 'Lote HID-02 recebido'],
            ['Éter Etílico P.A.', 1, pedido_compra::SOLICITADO, 0, null, 'Incluir no PCA do próximo ano'],
        ];
        foreach ($pedidos as [$nome, $qtd, $status, $mesesAtras, $entregaEmMeses, $obs]) {
            pedido_compra::forceCreate([
                'idreagente' => $this->reagentesCriados[$nome],
                'idusuario' => $this->usuarios['ana'],
                'quantidade' => $qtd,
                'status' => $status,
                'data_pedido' => $this->hoje->copy()->subMonths($mesesAtras)->toDateString(),
                'previsao_entrega' => $entregaEmMeses ? $this->hoje->copy()->addMonths($entregaEmMeses)->toDateString() : null,
                'data_recebimento' => $status === pedido_compra::RECEBIDO ? $this->hoje->copy()->subMonths(4)->toDateString() : null,
                'observacao' => $obs,
            ]);
        }
    }

    private array $reagentesCriados = [];

    // Ex.: "Hidróxido de Amônio 28%", lote 2 -> "HDA-02"
    private function codigoLote(string $nome, int $n): string
    {
        $palavras = array_filter(explode(' ', Str::ascii($nome)), fn ($p) => ctype_alpha($p[0] ?? '') && strlen($p) > 2);
        $sigla = strtoupper(implode('', array_map(fn ($p) => $p[0], array_slice(array_values($palavras), 0, 3))));
        return str_pad($sigla, 3, 'X') . '-' . sprintf('%02d', $n);
    }

    private function vidrarias(): void
    {
        // nome, catmat, local, quantidade comprada, quebras no ano
        $dados = [
            ['Béquer 250 mL', '274910', 'Lab. Química Geral - Armário V1', 40, 9],
            ['Béquer 50 mL', '274905', 'Lab. Química Geral - Armário V1', 30, 4],
            ['Erlenmeyer 250 mL', '275120', 'Lab. Química Analítica - Armário V2', 30, 6],
            ['Proveta graduada 100 mL', '276300', 'Lab. Química Geral - Armário V2', 20, 5],
            ['Pipeta volumétrica 10 mL', '277015', 'Lab. Química Analítica - Gaveta 3', 25, 7],
            ['Bureta 50 mL com torneira', '273880', 'Lab. Química Analítica - Suporte', 12, 2],
            ['Balão volumétrico 100 mL', '273550', 'Lab. Química Analítica - Armário V3', 15, 1],
            ['Vidro de relógio 80 mm', '278400', 'Lab. Química Geral - Gaveta 1', 20, 3],
        ];

        $motivos = [
            'Quebra' => ['Caiu da bancada durante a aula prática', 'Quebrou ao ser aquecido na chapa', 'Trincou durante a lavagem'],
            'Perda' => ['Não foi devolvido após a aula prática da turma'],
            'Defeito' => ['Graduação apagada, sem condições de uso'],
        ];

        foreach ($dados as [$nome, $catmat, $local, $comprada, $quebras]) {
            $inicio = $this->hoje->copy()->subMonths(12);
            $v = vidraria::forceCreate([
                'nome' => $nome, 'catmat' => $catmat, 'localizacao' => $local,
                'idlaboratorio' => str_contains($local, 'Analítica') ? $this->labs['analitica'] : $this->labs['geral'],
                'quantidade' => 0, 'ativo' => 1,
                'created_at' => $inicio, 'updated_at' => $inicio,
            ]);

            entrada_vidraria::forceCreate([
                'idvidraria' => $v->idvidraria, 'idlaboratorio' => $v->idlaboratorio,
                'idusuario' => $this->usuarios['ana'], 'quantidade' => $comprada,
                'data' => $inicio, 'observacao' => 'Compra via pregão eletrônico',
                'created_at' => $inicio, 'updated_at' => $inicio,
            ]);

            $estoque = $comprada;
            for ($i = 0; $i < $quebras; $i++) {
                $data = $this->hoje->copy()->subDays(mt_rand(5, 350));
                $motivo = array_rand($motivos);
                $ultima = $i === $quebras - 1;
                // A última baixa de alguns itens fica pendente ou recusada, para mostrar o fluxo de aprovação
                $status = $ultima && $quebras >= 6 ? saida_vidraria::PENDENTE
                    : ($ultima && $quebras === 5 ? saida_vidraria::RECUSADA : saida_vidraria::APROVADA);
                $data = $status === saida_vidraria::PENDENTE ? $this->hoje->copy()->subDays(mt_rand(0, 3)) : $data;

                saida_vidraria::forceCreate([
                    'idvidraria' => $v->idvidraria,
                    'idlaboratorio' => $v->idlaboratorio,
                    'idusuario' => [$this->usuarios['ana'], $this->usuarios['juliana'], $this->usuarios['lucas']][mt_rand(0, 2)],
                    'idaprovador' => $status === saida_vidraria::PENDENTE ? null : $this->usuarios['admin'],
                    'quantidade' => 1,
                    'status' => $status,
                    'motivo' => $motivo,
                    'data' => $data->toDateString(),
                    'data_decisao' => $status === saida_vidraria::PENDENTE ? null : $data->copy()->addDay(),
                    'observacao' => $motivos[$motivo][array_rand($motivos[$motivo])],
                    'motivo_recusa' => $status === saida_vidraria::RECUSADA ? 'Vidraria encontrada no armário da sala ao lado' : null,
                    'created_at' => $data, 'updated_at' => $data,
                ]);

                if ($status === saida_vidraria::APROVADA) {
                    $estoque--;
                }
            }

            $v->forceFill(['quantidade' => $estoque])->save();
        }
    }

    private function equipamentos(): void
    {
        // nome, catmat, patrimônio, status, [lab => quantidade]
        $dados = [
            ['Balança analítica 0,0001 g', '150420', 'IF-018734', 'Operacional', ['analitica' => 2]],
            ['pHmetro de bancada', '150877', 'IF-018802', 'Operacional', ['analitica' => 2, 'solos' => 1]],
            ['Capela de exaustão de gases', '151230', 'IF-016550', 'Operacional', ['geral' => 1, 'analitica' => 1]],
            ['Estufa de secagem e esterilização', '150990', 'IF-017203', 'Em Manutenção', ['geral' => 1]],
            ['Agitador magnético com aquecimento', '150655', 'IF-018915', 'Operacional', ['geral' => 4, 'analitica' => 2]],
            ['Espectrofotômetro UV-Vis', '151480', 'IF-019120', 'Operacional', ['solos' => 1]],
            ['Chapa aquecedora', '150700', 'IF-018660', 'Operacional', ['geral' => 3]],
        ];

        foreach ($dados as [$nome, $catmat, $patrimonio, $status, $locais]) {
            $data = $this->hoje->copy()->subMonths(mt_rand(10, 14));
            $total = array_sum($locais);
            $primeiroLab = array_key_first($locais);

            $e = equipamento::forceCreate([
                'nome' => $nome, 'catmat' => $catmat, 'status' => $status,
                'quantidade' => $total, 'ativo' => 1,
                'localizacao' => laboratorio::find($this->labs[$primeiroLab])->nome,
                'created_at' => $data, 'updated_at' => $data,
            ]);

            $sequencia = 1;
            foreach ($locais as $lab => $qtd) {
                equipamento_local::forceCreate([
                    'idequipamento' => $e->idequipamento, 'idlaboratorio' => $this->labs[$lab], 'quantidade' => $qtd,
                    'created_at' => $data, 'updated_at' => $data,
                ]);
                $entrada = entrada_equipamento::forceCreate([
                    'idequipamento' => $e->idequipamento, 'idlaboratorio' => $this->labs[$lab],
                    'idusuario' => $this->usuarios['admin'], 'quantidade' => $qtd,
                    'data' => $data, 'observacao' => 'Tombamento patrimonial',
                    'created_at' => $data, 'updated_at' => $data,
                ]);
                // Um número de patrimônio por unidade (ex.: IF-018734-01)
                for ($i = 0; $i < $qtd; $i++) {
                    equipamento_patrimonio::forceCreate([
                        'idequipamento' => $e->idequipamento, 'numero' => sprintf('%s-%02d', $patrimonio, $sequencia++),
                        'idlaboratorio' => $this->labs[$lab],
                        'identrada' => $entrada->identradaequipamento, 'created_at' => $data, 'updated_at' => $data,
                    ]);
                }
            }
        }
    }
}
