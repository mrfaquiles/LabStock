<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\entrada_reagente;
use App\Models\reagente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Cadastro de reagentes com controle por lote.
 *
 * O estoque (quantidade) é a soma dos saldos dos lotes e só muda por movimentação:
 * entradas de lote (entradareagentes) e registros de uso (saidareagentes).
 * O cadastro cria automaticamente o lote inicial.
 */
class reagenteController extends Controller
{
    /**
     * Regras de validação. Na edição os campos são opcionais (sometimes),
     * mas se enviados continuam obrigatórios.
     */
    private function regras(bool $edicao = false): array
    {
        $obrigatorio = $edicao ? 'sometimes|required' : 'required';

        $regras = [
            'nome' => "$obrigatorio|string|max:255",
            'catmat' => "$obrigatorio|string|max:50",
            'idunidademedida' => "$obrigatorio|exists:unidade_medidas,idunidademedida",
            'lote' => "$obrigatorio|string|max:255",
            'data_validade' => "$obrigatorio|date",
            'meses_alerta' => 'nullable|integer|min:1|max:36',
            'localizacao' => 'nullable|string|max:255',
            'descricao' => 'nullable|string',
            'imagem' => 'nullable|string|max:255',
            'ativo' => 'boolean',
        ];

        // Quantidade só no cadastro (lote inicial); depois, apenas por movimentação.
        // Sempre na unidade base (kg, L, un); frações em decimal (ex.: 0.5 kg)
        if (!$edicao) {
            $regras['quantidade'] = 'required|numeric|min:0|max:9999999';
        }

        return $regras;
    }

    /**
     * Acrescenta os lotes com saldo e a validade mais próxima entre eles.
     */
    private function comLotes(reagente $reagente): reagente
    {
        $lotes = $reagente->lotesComSaldo();
        $reagente->unsetRelation('entradas');
        $reagente->setAttribute('lotes', $lotes);
        $reagente->setAttribute('proxima_validade', $lotes[0]['data_validade'] ?? $reagente->data_validade);
        return $reagente;
    }

    private function carregar(string $id): ?reagente
    {
        $reagente = reagente::comLotes()->find($id);
        return $reagente ? $this->comLotes($reagente) : null;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reagentes = reagente::comLotes()->orderBy('nome')->get()
            ->map(fn ($reagente) => $this->comLotes($reagente));
        return response()->json($reagentes, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $dados = $request->validate($this->regras());
        $dados['meses_alerta'] = $dados['meses_alerta'] ?? Configuracao::valor('meses_alerta_padrao');

        $reagente = DB::transaction(function () use ($dados, $request) {
            $reagente = reagente::create($dados);

            // Lote inicial: todas as unidades compartilham a mesma validade
            if ((float) $dados['quantidade'] > 0) {
                entrada_reagente::create([
                    'idreagente' => $reagente->idreagente,
                    'idusuario' => $request->user()->id,
                    'quantidade' => $dados['quantidade'],
                    'lote' => $dados['lote'],
                    'data_validade' => $dados['data_validade'],
                    'data' => now()->toDateString(),
                    'observacao' => 'Lote inicial (cadastro do reagente)',
                ]);
            }

            return $reagente;
        });

        return response()->json($this->carregar($reagente->idreagente), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $reagente = $this->carregar($id);
        if (!$reagente) {
            return response()->json(['message' => 'Reagente não encontrado'], 404);
        }
        return response()->json($reagente, 200);
    }

    /**
     * Update the specified resource in storage.
     * A quantidade é ignorada: o estoque muda apenas por entrada de lote ou registro de uso.
     */
    public function update(Request $request, string $id)
    {
        $reagente = reagente::find($id);
        if (!$reagente) {
            return response()->json(['message' => 'Reagente não encontrado'], 404);
        }

        $dados = $request->validate($this->regras(true));
        if (array_key_exists('meses_alerta', $dados) && $dados['meses_alerta'] === null) {
            $dados['meses_alerta'] = Configuracao::valor('meses_alerta_padrao');
        }

        DB::transaction(function () use ($reagente, $dados) {
            // Correção do lote/validade do cadastro também corrige o lote inicial correspondente
            $loteAntigo = $reagente->lote;
            $validadeAntiga = $reagente->data_validade;

            $reagente->update($dados);

            if ($reagente->wasChanged(['lote', 'data_validade'])) {
                entrada_reagente::where('idreagente', $reagente->idreagente)
                    ->where('lote', $loteAntigo)
                    ->where('data_validade', $validadeAntiga)
                    ->update(['lote' => $reagente->lote, 'data_validade' => $reagente->data_validade]);
            }
        });

        return response()->json($this->carregar($id), 200);
    }

    /**
     * Remove the specified resource from storage.
     * Sem nenhum uso registrado (ex.: cadastro feito por engano), os lotes saem junto.
     * Com uso registrado, o histórico é preservado e a exclusão é recusada.
     */
    public function destroy(string $id)
    {
        $reagente = reagente::find($id);
        if (!$reagente) {
            return response()->json(['message' => 'Reagente não encontrado'], 404);
        }

        if ($reagente->saidas()->exists()) {
            return response()->json([
                'message' => 'Não é possível excluir: este reagente possui registros de uso. Desative-o para preservar o histórico.',
            ], 409);
        }

        DB::transaction(function () use ($reagente) {
            $reagente->entradas()->delete();
            $reagente->delete();
        });
        return response()->json(['message' => 'Reagente excluído com sucesso'], 200);
    }
}
