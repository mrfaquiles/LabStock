<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\equipamento;
use App\Models\equipamento_local;
use App\Models\laboratorio;
use App\Models\transferencia_equipamento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Equipamentos com estoque por laboratório (e unidade).
 * A quantidade total é a soma dos locais e muda por entrada, baixa ou transferência.
 * Toda edição do cadastro fica no histórico (trait RegistraHistorico).
 */
class equipamentoController extends Controller
{
    private array $relacoes = ['locais.laboratorio.unidade'];

    private function regras(bool $edicao = false): array
    {
        $obrigatorio = $edicao ? 'sometimes|required' : 'required';

        return [
            'nome' => "$obrigatorio|string|max:255",
            'catmat' => "$obrigatorio|string|max:50",
            'patrimonio' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'localizacao' => 'nullable|string|max:255',
            'descricao' => 'nullable|string',
            'ativo' => 'boolean',
        ];
    }

    public function index()
    {
        $equipamentos = equipamento::with($this->relacoes)->orderBy('nome')->get();
        return response()->json($equipamentos, 200);
    }

    /**
     * Cadastro com a quantidade inicial e o laboratório onde ela fica.
     */
    public function store(Request $request)
    {
        $dados = $request->validate($this->regras() + [
            'quantidade' => 'nullable|integer|min:0|max:100000',
            'idlaboratorio' => 'nullable|exists:laboratorios,idlaboratorio',
        ]);

        $equipamento = DB::transaction(function () use ($dados) {
            $quantidade = $dados['quantidade'] ?? 1;
            $idlaboratorio = $dados['idlaboratorio'] ?? null;
            unset($dados['idlaboratorio']);

            $equipamento = equipamento::create(array_merge($dados, ['quantidade' => $quantidade]));
            if ($quantidade > 0) {
                equipamento_local::create([
                    'idequipamento' => $equipamento->idequipamento,
                    'idlaboratorio' => $idlaboratorio,
                    'quantidade' => $quantidade,
                ]);
            }
            return $equipamento;
        });

        return response()->json($equipamento->load($this->relacoes), 201);
    }

    public function show(string $id)
    {
        $equipamento = equipamento::with($this->relacoes)->find($id);
        if (!$equipamento) {
            return response()->json(['message' => 'Equipamento não encontrado'], 404);
        }
        return response()->json($equipamento, 200);
    }

    /**
     * Edita o cadastro. A quantidade não muda aqui (use entrada, baixa ou transferência).
     */
    public function update(Request $request, string $id)
    {
        $equipamento = equipamento::find($id);
        if (!$equipamento) {
            return response()->json(['message' => 'Equipamento não encontrado'], 404);
        }

        $equipamento->update($request->validate($this->regras(true)));
        return response()->json($equipamento->load($this->relacoes), 200);
    }

    /**
     * Equipamentos com movimentações não podem ser excluídos (ver bootstrap/app.php).
     */
    public function destroy(string $id)
    {
        $equipamento = equipamento::find($id);
        if (!$equipamento) {
            return response()->json(['message' => 'Equipamento não encontrado'], 404);
        }
        $equipamento->delete();
        return response()->json(['message' => 'Equipamento excluído com sucesso'], 200);
    }

    /**
     * POST /equipamentos/{id}/transferir
     * Leva unidades de um laboratório para outro (inclusive entre unidades/campus).
     * idorigem nulo = unidades que estão "sem local definido".
     */
    public function transferir(Request $request, string $id)
    {
        $equipamento = equipamento::find($id);
        if (!$equipamento) {
            return response()->json(['message' => 'Equipamento não encontrado'], 404);
        }

        $dados = $request->validate([
            'idorigem' => 'nullable|exists:laboratorios,idlaboratorio',
            'iddestino' => 'required|exists:laboratorios,idlaboratorio|different:idorigem',
            'quantidade' => 'required|integer|min:1',
            'observacao' => 'nullable|string|max:255',
        ], [
            'iddestino.different' => 'O destino precisa ser diferente da origem.',
        ], [
            'iddestino' => 'destino',
            'idorigem' => 'origem',
        ]);

        return DB::transaction(function () use ($equipamento, $dados, $request) {
            $origem = equipamento_local::doLocal($equipamento->idequipamento, $dados['idorigem'] ?? null);
            if ($origem->quantidade < $dados['quantidade']) {
                return response()->json([
                    'message' => "Há apenas {$origem->quantidade} unidade(s) deste equipamento na origem.",
                ], 422);
            }

            $origem->decrement('quantidade', $dados['quantidade']);
            equipamento_local::doLocal($equipamento->idequipamento, $dados['iddestino'])
                ->increment('quantidade', $dados['quantidade']);

            $transferencia = transferencia_equipamento::create([
                'idequipamento' => $equipamento->idequipamento,
                'idorigem' => $dados['idorigem'] ?? null,
                'iddestino' => $dados['iddestino'],
                'quantidade' => $dados['quantidade'],
                'idusuario' => $request->user()->id,
                'data' => now(),
                'observacao' => $dados['observacao'] ?? null,
            ]);

            return response()->json([
                'transferencia' => $transferencia->load(['origem.unidade', 'destino.unidade']),
                'equipamento' => $equipamento->load($this->relacoes),
            ], 201);
        });
    }

    /**
     * GET /equipamentos/{id}/historico
     * Linha do tempo: edições do cadastro, entradas, baixas e transferências.
     */
    public function historico(string $id)
    {
        $equipamento = equipamento::find($id);
        if (!$equipamento) {
            return response()->json(['message' => 'Equipamento não encontrado'], 404);
        }

        $local = fn (?laboratorio $lab) => $lab
            ? trim(($lab->unidade ? "{$lab->unidade->nome} › " : '') . $lab->nome)
            : 'Sem local definido';

        $eventos = collect();

        foreach ($equipamento->historico()->with('usuario:id,nome')->get() as $h) {
            $eventos->push([
                'tipo' => $h->acao, // criado | alterado | excluido
                'data' => $h->created_at,
                'usuario' => $h->usuario?->nome,
                'alteracoes' => $h->alteracoes,
            ]);
        }

        foreach ($equipamento->transferencias()->with(['origem.unidade', 'destino.unidade', 'usuario:id,nome'])->get() as $t) {
            $eventos->push([
                'tipo' => 'transferencia',
                'data' => $t->created_at,
                'usuario' => $t->usuario?->nome,
                'quantidade' => $t->quantidade,
                'origem' => $local($t->origem),
                'destino' => $local($t->destino),
                'observacao' => $t->observacao,
            ]);
        }

        foreach ($equipamento->entradas()->with(['laboratorio.unidade', 'usuario:id,nome'])->get() as $e) {
            $eventos->push([
                'tipo' => 'entrada',
                'data' => $e->created_at,
                'usuario' => $e->usuario?->nome,
                'quantidade' => $e->quantidade,
                'destino' => $local($e->laboratorio),
                'observacao' => $e->observacao,
            ]);
        }

        foreach ($equipamento->saidas()->with(['laboratorio.unidade', 'usuario:id,nome'])->get() as $s) {
            $eventos->push([
                'tipo' => 'baixa',
                'data' => $s->created_at,
                'usuario' => $s->usuario?->nome,
                'quantidade' => $s->quantidade,
                'origem' => $local($s->laboratorio),
                'observacao' => $s->observacao,
            ]);
        }

        return response()->json($eventos->sortByDesc('data')->values(), 200);
    }
}
