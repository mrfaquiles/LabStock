<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\entrada_reagente;
use App\Models\entrada_vidraria;
use App\Models\pedido_compra;
use App\Models\reagente;
use App\Models\vidraria;
use App\Services\PrevisaoCompras;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Compras do laboratório: previsão de demanda para a licitação e
 * acompanhamento dos pedidos até a entrega.
 */
class PedidoCompraController extends Controller
{
    private array $relacoes = ['reagente.unidadeMedida', 'vidraria', 'usuario:id,nome'];

    /**
     * GET /compras/previsao?meses_base=12&meses_cobrir=12&margem=20&categoria=todas
     */
    public function previsao(Request $request, PrevisaoCompras $previsao)
    {
        $dados = $request->validate([
            'meses_base' => 'nullable|integer|min:1|max:36',
            'meses_cobrir' => 'nullable|integer|min:1|max:36',
            'margem' => 'nullable|integer|min:0|max:200',
            'categoria' => 'nullable|in:todas,reagentes,vidrarias',
        ]);

        $mesesBase = $dados['meses_base'] ?? 12;
        $mesesCobrir = $dados['meses_cobrir'] ?? 12;
        $margem = $dados['margem'] ?? Configuracao::valor('margem_seguranca_percentual');
        $categoria = $dados['categoria'] ?? 'todas';

        return response()->json([
            'parametros' => [
                'meses_base' => $mesesBase,
                'meses_cobrir' => $mesesCobrir,
                'margem' => $margem,
                'tempo_compra_meses' => Configuracao::valor('tempo_compra_meses'),
            ],
            'itens' => $previsao->calcular(
                $mesesBase, $mesesCobrir, $margem,
                $categoria === 'todas' ? ['reagentes', 'vidrarias'] : [$categoria]
            ),
        ], 200);
    }

    /**
     * Lista os pedidos. Filtro opcional: ?situacao=andamento | historico
     */
    public function index(Request $request)
    {
        $consulta = pedido_compra::with($this->relacoes)->orderByDesc('data_pedido')->orderByDesc('idpedido');

        if ($request->input('situacao') === 'andamento') {
            $consulta->emAndamento();
        } elseif ($request->input('situacao') === 'historico') {
            $consulta->whereNotIn('status', pedido_compra::EM_ANDAMENTO);
        }

        return response()->json($consulta->get(), 200);
    }

    public function show(string $id)
    {
        $pedido = pedido_compra::with($this->relacoes)->find($id);
        if (!$pedido) {
            return response()->json(['message' => 'Pedido não encontrado'], 404);
        }
        return response()->json($pedido, 200);
    }

    public function store(Request $request)
    {
        $dados = $request->validate([
            'idreagente' => 'nullable|required_without:idvidraria|prohibits:idvidraria|exists:reagentes,idreagente',
            'idvidraria' => 'nullable|required_without:idreagente|exists:vidrarias,idvidraria',
            'quantidade' => 'required|numeric|gt:0|max:9999999',
            'data_pedido' => 'nullable|date',
            'previsao_entrega' => 'nullable|date',
            'observacao' => 'nullable|string|max:255',
        ], [
            'idreagente.required_without' => 'Escolha o reagente ou a vidraria do pedido.',
            'idvidraria.required_without' => 'Escolha o reagente ou a vidraria do pedido.',
            'idreagente.prohibits' => 'Um pedido é de um reagente ou de uma vidraria, não dos dois.',
        ]);

        if (!empty($dados['idvidraria']) && floor($dados['quantidade']) != $dados['quantidade']) {
            return response()->json(['message' => 'Vidraria é pedida em unidades inteiras.'], 422);
        }

        $dados['idusuario'] = $request->user()->id;
        $dados['data_pedido'] = $dados['data_pedido'] ?? now()->toDateString();
        $dados['status'] = pedido_compra::SOLICITADO;

        $pedido = pedido_compra::create($dados);
        return response()->json($pedido->load($this->relacoes), 201);
    }

    /**
     * Atualiza andamento (status), previsão de entrega, quantidade ou observação.
     * O recebimento tem rota própria, porque registra a entrada no estoque.
     */
    public function update(Request $request, string $id)
    {
        $pedido = pedido_compra::find($id);
        if (!$pedido) {
            return response()->json(['message' => 'Pedido não encontrado'], 404);
        }
        if (!in_array($pedido->status, pedido_compra::EM_ANDAMENTO, true)) {
            return response()->json(['message' => 'Pedidos recebidos ou cancelados não podem ser alterados.'], 422);
        }

        $dados = $request->validate([
            'status' => ['sometimes', Rule::in([
                pedido_compra::SOLICITADO, pedido_compra::EM_LICITACAO,
                pedido_compra::AGUARDANDO_ENTREGA, pedido_compra::CANCELADO,
            ])],
            'quantidade' => 'sometimes|numeric|gt:0|max:9999999',
            'previsao_entrega' => 'nullable|date',
            'observacao' => 'nullable|string|max:255',
        ], [
            'status.in' => 'Para marcar como recebido, use a opção "Receber".',
        ]);

        $pedido->update($dados);
        return response()->json($pedido->load($this->relacoes), 200);
    }

    /**
     * POST /pedidoscompra/{id}/receber
     * Marca como recebido e registra a entrada no estoque.
     * Reagente: exige lote e validade (o lote chega com a mesma validade para todas as unidades).
     */
    public function receber(Request $request, string $id)
    {
        $pedido = pedido_compra::find($id);
        if (!$pedido) {
            return response()->json(['message' => 'Pedido não encontrado'], 404);
        }
        if (!in_array($pedido->status, pedido_compra::EM_ANDAMENTO, true)) {
            return response()->json(['message' => "Este pedido já está {$pedido->status}."], 422);
        }

        $ehReagente = (bool) $pedido->idreagente;
        $dados = $request->validate([
            'quantidade' => 'required|numeric|gt:0|max:9999999',
            'lote' => [Rule::requiredIf($ehReagente), 'nullable', 'string', 'max:255'],
            'data_validade' => [Rule::requiredIf($ehReagente), 'nullable', 'date'],
            'observacao' => 'nullable|string|max:255',
        ]);

        if (!$ehReagente && floor($dados['quantidade']) != $dados['quantidade']) {
            return response()->json(['message' => 'Vidraria é recebida em unidades inteiras.'], 422);
        }

        DB::transaction(function () use ($pedido, $dados, $ehReagente, $request) {
            $observacao = $dados['observacao'] ?? "Recebimento do pedido de compra nº {$pedido->idpedido}";

            if ($ehReagente) {
                entrada_reagente::create([
                    'idreagente' => $pedido->idreagente,
                    'idusuario' => $request->user()->id,
                    'quantidade' => $dados['quantidade'],
                    'lote' => $dados['lote'],
                    'data_validade' => $dados['data_validade'],
                    'data' => now()->toDateString(),
                    'observacao' => $observacao,
                ]);
                reagente::lockForUpdate()->find($pedido->idreagente)->increment('quantidade', $dados['quantidade']);
            } else {
                entrada_vidraria::create([
                    'idvidraria' => $pedido->idvidraria,
                    'idusuario' => $request->user()->id,
                    'quantidade' => $dados['quantidade'],
                    'data' => now(),
                    'observacao' => $observacao,
                ]);
                vidraria::lockForUpdate()->find($pedido->idvidraria)->increment('quantidade', $dados['quantidade']);
            }

            $pedido->update([
                'status' => pedido_compra::RECEBIDO,
                'data_recebimento' => now()->toDateString(),
            ]);
        });

        return response()->json($pedido->fresh()->load($this->relacoes), 200);
    }

    /**
     * Exclui um pedido lançado por engano (só enquanto estiver como "solicitado").
     * Depois disso, use o status "cancelado" para manter o histórico.
     */
    public function destroy(string $id)
    {
        $pedido = pedido_compra::find($id);
        if (!$pedido) {
            return response()->json(['message' => 'Pedido não encontrado'], 404);
        }
        if ($pedido->status !== pedido_compra::SOLICITADO) {
            return response()->json(['message' => 'Só pedidos ainda "solicitados" podem ser excluídos. Use "Cancelar" para manter o histórico.'], 422);
        }
        $pedido->delete();
        return response()->json(['message' => 'Pedido excluído'], 200);
    }
}
