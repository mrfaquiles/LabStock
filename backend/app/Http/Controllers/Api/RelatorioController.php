<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\entrada_equipamento;
use App\Models\entrada_reagente;
use App\Models\entrada_vidraria;
use App\Models\equipamento;
use App\Models\reagente;
use App\Models\saida_equipamento;
use App\Models\saida_reagente;
use App\Models\saida_vidraria;
use App\Models\vidraria;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Relatórios gerenciais por período:
 *  - estoque: saldo no início, entradas, saídas e saldo no fim do período
 *  - gastos:  consumo/perdas do período, com média mensal e cobertura do estoque
 *             (apoio à previsão de compras)
 */
class RelatorioController extends Controller
{
    /**
     * Configuração de cada categoria: [item, entrada, saída, chave, unidade fixa]
     */
    private function categorias(): array
    {
        return [
            'reagentes' => [reagente::class, entrada_reagente::class, saida_reagente::class, 'idreagente', null],
            'vidrarias' => [vidraria::class, entrada_vidraria::class, saida_vidraria::class, 'idvidraria', 'un'],
            'equipamentos' => [equipamento::class, entrada_equipamento::class, saida_equipamento::class, 'idequipamento', 'un'],
        ];
    }

    private function filtros(Request $request): array
    {
        $dados = $request->validate([
            'data_inicio' => 'required|date',
            'data_fim' => 'required|date|after_or_equal:data_inicio',
            'categoria' => 'nullable|in:todas,reagentes,vidrarias,equipamentos',
        ], [
            'data_fim.after_or_equal' => 'A data final deve ser igual ou posterior à data inicial.',
        ]);

        $categoria = $dados['categoria'] ?? 'todas';

        return [
            Carbon::parse($dados['data_inicio'])->toDateString(),
            // Inclui o dia final inteiro (algumas colunas `data` guardam hora)
            Carbon::parse($dados['data_fim'])->toDateString() . ' 23:59:59',
            $categoria === 'todas' ? array_keys($this->categorias()) : [$categoria],
        ];
    }

    /** Soma das quantidades por item, opcionalmente entre duas datas. */
    private function somaPorItem(string $model, string $chave, ?string $de = null, ?string $ate = null): array
    {
        $consulta = $model::query()->selectRaw("$chave as item, SUM(quantidade) as total")->groupBy($chave);
        // Baixas de vidraria pendentes/recusadas não saíram do estoque
        if (method_exists($model, 'scopeAprovadas')) {
            $consulta->aprovadas();
        }
        if ($de) {
            $consulta->where('data', '>=', $de);
        }
        if ($ate) {
            $consulta->where('data', '<=', $ate);
        }
        return $consulta->pluck('total', 'item')->map(fn ($v) => (float) $v)->all();
    }

    /**
     * GET /relatorios/estoque — posição do estoque no período.
     * O saldo histórico é calculado a partir do estoque atual, desfazendo as
     * movimentações posteriores ao fim do período.
     */
    public function estoque(Request $request)
    {
        [$inicio, $fim, $categorias] = $this->filtros($request);
        $linhas = [];

        foreach ($categorias as $nomeCategoria) {
            [$itemModel, $entradaModel, $saidaModel, $chave, $unidadeFixa] = $this->categorias()[$nomeCategoria];

            $entradasPeriodo = $this->somaPorItem($entradaModel, $chave, $inicio, $fim);
            $saidasPeriodo = $this->somaPorItem($saidaModel, $chave, $inicio, $fim);
            $entradasTotal = $this->somaPorItem($entradaModel, $chave, null, null);
            $saidasTotal = $this->somaPorItem($saidaModel, $chave, null, null);
            // Movimentações após o fim do período = total - até o fim
            $entradasAteFim = $this->somaPorItem($entradaModel, $chave, null, $fim);
            $saidasAteFim = $this->somaPorItem($saidaModel, $chave, null, $fim);

            $itens = $nomeCategoria === 'reagentes'
                ? $itemModel::comLotes()->orderBy('nome')->get()
                : $itemModel::orderBy('nome')->get();

            foreach ($itens as $item) {
                $id = $item->{$chave};
                $atual = (float) $item->quantidade;
                $entradasPosteriores = ($entradasTotal[$id] ?? 0) - ($entradasAteFim[$id] ?? 0);
                $saidasPosteriores = ($saidasTotal[$id] ?? 0) - ($saidasAteFim[$id] ?? 0);

                $saldoFinal = $atual - $entradasPosteriores + $saidasPosteriores;
                $entradas = $entradasPeriodo[$id] ?? 0;
                $saidas = $saidasPeriodo[$id] ?? 0;

                $linhas[] = [
                    'categoria' => $nomeCategoria,
                    'id' => $id,
                    'nome' => $item->nome,
                    'catmat' => $item->catmat,
                    'unidade' => $unidadeFixa ?? $item->unidadeMedida?->sigla,
                    'saldo_inicial' => round($saldoFinal - $entradas + $saidas, 6),
                    'entradas' => round($entradas, 6),
                    'saidas' => round($saidas, 6),
                    'saldo_final' => round($saldoFinal, 6),
                    'localizacao' => $item->localizacao ?? null,
                    'status' => $item->status ?? null,
                    'ativo' => (bool) $item->ativo,
                    // Lotes com saldo atual (somente reagentes)
                    'lotes' => $nomeCategoria === 'reagentes' ? $item->lotesComSaldo() : [],
                ];
            }
        }

        return response()->json([
            'periodo' => ['inicio' => $inicio, 'fim' => substr($fim, 0, 10)],
            'itens' => $linhas,
        ], 200);
    }

    /**
     * GET /relatorios/controlados — prestação de contas dos produtos controlados:
     * saldo do período e cada entrada e uso, com lote, usuário, data e justificativa.
     * Filtro opcional: ?idreagente=3
     */
    public function controlados(Request $request)
    {
        [$inicio, $fim] = $this->filtros($request);
        $request->validate(['idreagente' => 'nullable|exists:reagentes,idreagente']);

        $reagentes = reagente::with('unidadeMedida')
            ->where('controlado', true)
            ->when($request->filled('idreagente'), fn ($q) => $q->where('idreagente', $request->input('idreagente')))
            ->orderBy('nome')
            ->get()
            ->keyBy('idreagente');
        $ids = $reagentes->keys();

        $linha = fn ($mov, string $tipo, ?string $lote, $validade) => [
            'tipo' => $tipo,
            'data' => substr((string) $mov->data, 0, 10),
            'registrado_em' => $mov->created_at?->toDateTimeString(),
            'idreagente' => $mov->idreagente,
            'reagente' => $reagentes[$mov->idreagente]->nome,
            'lote' => $lote,
            'data_validade' => $validade,
            'quantidade' => (float) $mov->quantidade,
            'unidade' => $reagentes[$mov->idreagente]->unidadeMedida?->sigla,
            'usuario' => $mov->usuario?->nome,
            'justificativa' => $mov->observacao,
        ];

        $entradas = entrada_reagente::with('usuario:id,nome')
            ->whereIn('idreagente', $ids)->whereBetween('data', [$inicio, $fim])->get()
            ->map(fn ($e) => $linha($e, 'entrada', $e->lote, $e->data_validade));

        $usos = saida_reagente::with(['usuario:id,nome', 'entrada'])
            ->whereIn('idreagente', $ids)->whereBetween('data', [$inicio, $fim])->get()
            ->map(fn ($s) => $linha($s, 'uso', $s->entrada?->lote, $s->entrada?->data_validade));

        // Mesmo cálculo do relatório de estoque: parte do saldo atual e desfaz o que veio depois do período
        $entradasPeriodo = $this->somaPorItem(entrada_reagente::class, 'idreagente', $inicio, $fim);
        $saidasPeriodo = $this->somaPorItem(saida_reagente::class, 'idreagente', $inicio, $fim);
        $entradasAteFim = $this->somaPorItem(entrada_reagente::class, 'idreagente', null, $fim);
        $saidasAteFim = $this->somaPorItem(saida_reagente::class, 'idreagente', null, $fim);
        $entradasTotal = $this->somaPorItem(entrada_reagente::class, 'idreagente');
        $saidasTotal = $this->somaPorItem(saida_reagente::class, 'idreagente');

        $resumo = $reagentes->values()->map(function ($r) use ($entradasPeriodo, $saidasPeriodo, $entradasAteFim, $saidasAteFim, $entradasTotal, $saidasTotal, $usos) {
            $id = $r->idreagente;
            $saldoFinal = (float) $r->quantidade
                - (($entradasTotal[$id] ?? 0) - ($entradasAteFim[$id] ?? 0))
                + (($saidasTotal[$id] ?? 0) - ($saidasAteFim[$id] ?? 0));
            $entrou = $entradasPeriodo[$id] ?? 0;
            $saiu = $saidasPeriodo[$id] ?? 0;

            return [
                'idreagente' => $id,
                'nome' => $r->nome,
                'catmat' => $r->catmat,
                'cas' => $r->cas,
                'classificacao_controle' => $r->classificacao_controle,
                'unidade' => $r->unidadeMedida?->sigla,
                'saldo_inicial' => round($saldoFinal - $entrou + $saiu, 6),
                'entradas' => round($entrou, 6),
                'usos' => round($saiu, 6),
                'saldo_final' => round($saldoFinal, 6),
                // Usos antigos, de antes de o produto ser marcado como controlado
                'usos_sem_justificativa' => $usos->where('idreagente', $id)->filter(fn ($u) => !trim((string) $u['justificativa']))->count(),
            ];
        });

        return response()->json([
            'periodo' => ['inicio' => $inicio, 'fim' => substr($fim, 0, 10)],
            'resumo' => $resumo,
            'movimentacoes' => $entradas->concat($usos)->sortBy(['data', 'registrado_em'])->values(),
        ], 200);
    }

    /**
     * GET /relatorios/gastos — consumo de reagentes, quebras/perdas de vidrarias
     * e saídas de equipamentos no período.
     */
    public function gastos(Request $request)
    {
        [$inicio, $fim, $categorias] = $this->filtros($request);

        // Dias inteiros do período, contando o dia inicial e o final
        $dias = (int) Carbon::parse($inicio)->diffInDays(Carbon::parse(substr($fim, 0, 10))) + 1;
        $meses = max($dias / 30.44, 1 / 30.44);

        $detalhes = [];
        $resumo = [];

        foreach ($categorias as $nomeCategoria) {
            [$itemModel, , $saidaModel, $chave, $unidadeFixa] = $this->categorias()[$nomeCategoria];

            $relacoes = ['usuario:id,nome', 'item'];
            if ($nomeCategoria === 'reagentes') {
                $relacoes[] = 'entrada';
                $relacoes[] = 'item.unidadeMedida';
            } else {
                $relacoes[] = 'laboratorio';
            }

            $consulta = $saidaModel::with($relacoes)
                ->whereBetween('data', [$inicio, $fim])
                ->orderBy('data');
            if (method_exists($saidaModel, 'scopeAprovadas')) {
                $consulta->aprovadas();
            }
            $saidas = $consulta->get();

            foreach ($saidas as $saida) {
                $unidade = $unidadeFixa ?? $saida->item?->unidadeMedida?->sigla;
                $id = $saida->{$chave};

                $detalhes[] = [
                    'categoria' => $nomeCategoria,
                    'data' => substr((string) $saida->data, 0, 10),
                    'item' => $saida->item?->nome,
                    'lote' => $nomeCategoria === 'reagentes' ? $saida->entrada?->lote : null,
                    'laboratorio' => $saida->laboratorio?->nome ?? null,
                    'quantidade' => (float) $saida->quantidade,
                    'unidade' => $unidade,
                    'usuario' => $saida->usuario?->nome,
                    // Vidrarias: motivo da baixa + justificativa
                    'observacao' => $saida->motivo ? "{$saida->motivo}: {$saida->observacao}" : $saida->observacao,
                ];

                $chaveResumo = "$nomeCategoria-$id";
                $resumo[$chaveResumo] ??= [
                    'categoria' => $nomeCategoria,
                    'item' => $saida->item?->nome,
                    'catmat' => $saida->item?->catmat,
                    'unidade' => $unidade,
                    'registros' => 0,
                    'total' => 0,
                    'estoque_atual' => (float) $saida->item?->quantidade,
                ];
                $resumo[$chaveResumo]['registros']++;
                $resumo[$chaveResumo]['total'] += (float) $saida->quantidade;
            }
        }

        // Média mensal e quantos meses o estoque atual dura nesse ritmo de consumo
        $resumo = collect($resumo)->map(function ($linha) use ($meses) {
            $linha['total'] = round($linha['total'], 6);
            $linha['media_mensal'] = round($linha['total'] / $meses, 6);
            $linha['cobertura_meses'] = $linha['media_mensal'] > 0
                ? round($linha['estoque_atual'] / $linha['media_mensal'], 1)
                : null;
            return $linha;
        })->sortByDesc('total')->values();

        return response()->json([
            'periodo' => ['inicio' => $inicio, 'fim' => substr($fim, 0, 10), 'dias' => $dias],
            'resumo' => $resumo,
            'detalhes' => $detalhes,
        ], 200);
    }
}
