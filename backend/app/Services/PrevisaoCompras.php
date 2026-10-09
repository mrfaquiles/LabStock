<?php

namespace App\Services;

use App\Models\Configuracao;
use App\Models\pedido_compra;
use App\Models\reagente;
use App\Models\saida_reagente;
use App\Models\saida_vidraria;
use App\Models\vidraria;
use App\Support\Quantidade;
use Illuminate\Support\Carbon;

/**
 * Previsão de compras (demanda para a licitação).
 *
 * Para cada reagente e vidraria ativos:
 *   média mensal      = consumo no período base / meses do período
 *   necessidade       = média × meses a cobrir × (1 + margem)
 *   quantidade sugerida = necessidade − estoque útil − quantidade já pedida
 *
 * Estoque útil de reagente = saldo dos lotes ainda não vencidos.
 * Consumo de vidraria = baixas aprovadas (quebras, perdas...).
 */
class PrevisaoCompras
{
    public const SITUACOES = ['pedir_agora', 'planejar', 'em_pedido', 'ok', 'sem_consumo'];

    public function calcular(int $mesesBase, int $mesesCobrir, int $margemPercentual, array $categorias): array
    {
        $tempoCompra = Configuracao::valor('tempo_compra_meses');
        $inicio = Carbon::today()->subMonthsNoOverflow($mesesBase)->toDateString();
        $hoje = Carbon::today()->toDateString();
        $itens = [];

        if (in_array('reagentes', $categorias, true)) {
            $consumo = $this->somar(saida_reagente::query(), 'idreagente', $inicio);
            $pedido = $this->somar(pedido_compra::emAndamento(), 'idreagente');

            foreach (reagente::comLotes()->where('ativo', 1)->orderBy('nome')->get() as $r) {
                $estoqueUtil = collect($r->lotesComSaldo())
                    ->filter(fn ($l) => !$l['data_validade'] || $l['data_validade'] >= $hoje)
                    ->sum('saldo');

                $itens[] = $this->linha(
                    'reagentes', $r->idreagente, $r->nome, $r->catmat, $r->unidadeMedida?->sigla ?? '',
                    $consumo[$r->idreagente] ?? 0, $estoqueUtil, $pedido[$r->idreagente] ?? 0,
                    $mesesBase, $mesesCobrir, $margemPercentual, $tempoCompra, false
                );
            }
        }

        if (in_array('vidrarias', $categorias, true)) {
            $consumo = $this->somar(saida_vidraria::aprovadas(), 'idvidraria', $inicio);
            $pedido = $this->somar(pedido_compra::emAndamento(), 'idvidraria');

            foreach (vidraria::where('ativo', 1)->orderBy('nome')->get() as $v) {
                $itens[] = $this->linha(
                    'vidrarias', $v->idvidraria, $v->nome, $v->catmat, 'un',
                    $consumo[$v->idvidraria] ?? 0, (float) $v->quantidade, $pedido[$v->idvidraria] ?? 0,
                    $mesesBase, $mesesCobrir, $margemPercentual, $tempoCompra, true
                );
            }
        }

        // Mais urgentes primeiro
        usort($itens, fn ($a, $b) => [array_search($a['situacao'], self::SITUACOES), $a['nome']]
            <=> [array_search($b['situacao'], self::SITUACOES), $b['nome']]);

        return $itens;
    }

    private function somar($consulta, string $chave, ?string $desde = null): array
    {
        if ($desde) {
            $consulta->where('data', '>=', $desde);
        }
        return $consulta->whereNotNull($chave)
            ->selectRaw("$chave as item, SUM(quantidade) as total")
            ->groupBy($chave)
            ->pluck('total', 'item')
            ->map(fn ($v) => (float) $v)
            ->all();
    }

    private function linha(
        string $categoria, int $id, string $nome, ?string $catmat, string $unidade,
        float $consumo, float $estoque, float $emPedido,
        int $mesesBase, int $mesesCobrir, int $margem, int $tempoCompra, bool $inteiro
    ): array {
        $media = $consumo / $mesesBase;
        $necessidade = $media * $mesesCobrir * (1 + $margem / 100);
        $falta = max(0, $necessidade - $estoque - $emPedido);
        // Vidraria é comprada por unidade inteira; reagente com até 3 casas (como no banco)
        $sugerido = $inteiro ? (float) ceil($falta - 1e-9) : ceil($falta * 1000 - 1e-6) / 1000;
        // ceil() de um valor pouco abaixo de zero devolve -0.0, que aparecia como "-0" na tela
        $sugerido = $sugerido > 0 ? $sugerido : 0.0;
        $cobertura = $media > 0 ? round($estoque / $media, 1) : null;

        $situacao = match (true) {
            $media <= 0 => 'sem_consumo',
            $sugerido > 0 && $cobertura < $tempoCompra => 'pedir_agora',
            $sugerido > 0 => 'planejar',
            $emPedido > 0 => 'em_pedido',
            default => 'ok',
        };

        // Quantidades na unidade mais legível (ex.: 0,0005 kg → 0,5 g)
        $q = fn (float $valor) => Quantidade::formatar($valor, $unidade);
        $justificativa = $media > 0
            ? sprintf(
                'Consumo de %s nos últimos %d meses (média de %s/mês). Para %d meses, com margem de %d%%, são necessários %s. Estoque disponível: %s; já pedido: %s.',
                $q($consumo), $mesesBase, $q($media),
                $mesesCobrir, $margem, $q($necessidade),
                $q($estoque), $q($emPedido)
            )
            : "Sem consumo registrado nos últimos {$mesesBase} meses.";

        return [
            'categoria' => $categoria,
            'id' => $id,
            'nome' => $nome,
            'catmat' => $catmat,
            'unidade' => $unidade,
            'consumo' => round($consumo, 6),
            'media_mensal' => round($media, 6),
            'estoque' => round($estoque, 6),
            'em_pedido' => round($emPedido, 6),
            'necessidade' => round($necessidade, 6),
            'sugerido' => $sugerido,
            'cobertura_meses' => $cobertura,
            'situacao' => $situacao,
            'justificativa' => $justificativa,
        ];
    }
}
