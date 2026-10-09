<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\equipamento;
use App\Models\pedido_compra;
use App\Services\PrevisaoCompras;
use App\Models\reagente;
use App\Models\saida_vidraria;
use App\Models\vidraria;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Resumo do estoque e alertas de vencimento de reagentes.
     * Cada reagente usa o próprio `meses_alerta` (padrão 4) como antecedência.
     */
    public function index(PrevisaoCompras $previsao)
    {
        $hoje = Carbon::today();

        // Itens cujo estoque não dura até uma compra nova chegar (tempo de licitação)
        $pedirAgora = collect($previsao->calcular(
            12, 12, Configuracao::valor('margem_seguranca_percentual'), ['reagentes', 'vidrarias']
        ))->where('situacao', 'pedir_agora')->values();

        $reagentes = reagente::comLotes()->where('ativo', 1)->get();

        $vencidos = [];
        $proximos = [];

        // O alerta é por lote: só lotes que ainda têm saldo em estoque
        foreach ($reagentes as $reagente) {
            $meses = $reagente->meses_alerta ?: 4;

            foreach ($reagente->lotesComSaldo() as $lote) {
                if (!$lote['data_validade']) {
                    continue;
                }

                $validade = Carbon::parse($lote['data_validade'])->startOfDay();
                $item = [
                    'idreagente' => $reagente->idreagente,
                    'nome' => $reagente->nome,
                    'lote' => $lote['lote'],
                    'quantidade' => $lote['saldo'],
                    'unidade' => $reagente->unidadeMedida?->sigla,
                    'localizacao' => $reagente->localizacao,
                    'data_validade' => $validade->toDateString(),
                    'meses_alerta' => $meses,
                    'dias_restantes' => (int) $hoje->diffInDays($validade, false),
                ];

                if ($validade->lt($hoje)) {
                    $vencidos[] = $item;
                } elseif ($validade->lte($hoje->copy()->addMonthsNoOverflow($meses))) {
                    $proximos[] = $item;
                }
            }
        }

        usort($vencidos, fn ($a, $b) => $a['dias_restantes'] <=> $b['dias_restantes']);
        usort($proximos, fn ($a, $b) => $a['dias_restantes'] <=> $b['dias_restantes']);

        return response()->json([
            'totais' => [
                'reagentes' => reagente::where('ativo', 1)->count(),
                'vidrarias' => vidraria::where('ativo', 1)->count(),
                'equipamentos' => equipamento::where('ativo', 1)->count(),
                'equipamentos_manutencao' => equipamento::where('ativo', 1)->where('status', 'Em Manutenção')->count(),
            ],
            'alertas' => [
                'vencidos' => $vencidos,
                'proximos_vencimento' => $proximos,
            ],
            // Quebras/perdas dos últimos 30 dias, para apoiar a previsão de compras
            'quebras_vidraria_30_dias' => (int) saida_vidraria::aprovadas()
                ->where('data', '>=', $hoje->copy()->subDays(30)->toDateString())
                ->sum('quantidade'),
            'baixas_vidraria_pendentes' => saida_vidraria::where('status', saida_vidraria::PENDENTE)->count(),
            'compras_pedir_agora' => $pedirAgora->count(),
            'compras_pedir_agora_itens' => $pedirAgora->pluck('nome')->take(5)->all(),
            'pedidos_em_andamento' => pedido_compra::emAndamento()->count(),
        ], 200);
    }
}
