<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use App\Models\entrada_reagente;
use App\Models\reagente;
use App\Rules\CodigoCas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
    private function regras(?int $id = null): array
    {
        $edicao = $id !== null;
        $obrigatorio = $edicao ? ['sometimes', 'required'] : ['required'];

        $regras = [
            'nome' => [...$obrigatorio, 'string', 'max:255', Rule::unique('reagentes', 'nome')->ignore($id, 'idreagente')],
            'catmat' => [...$obrigatorio, 'string', 'max:50', Rule::unique('reagentes', 'catmat')->ignore($id, 'idreagente')],
            'cas' => ['nullable', 'string', 'max:20', new CodigoCas],
            'controlado' => 'boolean',
            'classificacao_controle' => 'nullable|string|max:100',
            'idunidademedida' => [...$obrigatorio, 'exists:unidade_medidas,idunidademedida'],
            'meses_alerta' => 'nullable|integer|min:1|max:36',
            'localizacao' => 'nullable|string|max:255',
            'descricao' => 'nullable|string',
            'imagem' => 'nullable|string|max:255',
            'ativo' => 'boolean',
        ];

        // Só no cadastro: quantidade e dados do lote inicial. Depois, tudo muda por movimentação.
        // Quantidade sempre na unidade base (kg, L, un); frações em decimal (ex.: 0.5 kg)
        if (!$edicao) {
            $quantidade = request()->input('quantidade');
            $temLoteInicial = !is_numeric($quantidade) || (float) $quantidade > 0;

            $regras['quantidade'] = ['required', 'numeric', 'min:0', 'max:9999999', function ($campo, $valor, $falhar) {
                // Abaixo de 1 mg / 1 µL o banco arredondaria para zero
                if ((float) $valor > 0 && round((float) $valor, 6) <= 0) {
                    $falhar('A quantidade mínima é de 1 mg ou 1 µL.');
                }
            }];
            $regras['lote'] = [Rule::requiredIf($temLoteInicial), 'nullable', 'string', 'max:255'];
            $regras['data_validade'] = [Rule::requiredIf($temLoteInicial), 'nullable', 'date'];
            $regras['idlaboratorio'] = [Rule::requiredIf($temLoteInicial), 'nullable', 'exists:laboratorios,idlaboratorio'];
            // Origem do lote inicial (nota fiscal, doação...): opcional
            $regras['origem'] = 'nullable|string|max:255';
        }

        return $regras;
    }

    private function mensagens(): array
    {
        return [
            'nome.unique' => 'Já existe um reagente com este nome.',
            'catmat.unique' => 'Já existe um reagente com este CATMAT.',
            'idlaboratorio.required' => 'Informe o laboratório do lote inicial.',
        ];
    }

    /**
     * Acrescenta os lotes com saldo e a validade mais próxima entre eles.
     */
    private function comLotes(reagente $reagente): reagente
    {
        $lotes = $reagente->lotesComSaldo();
        $reagente->unsetRelation('entradas');
        $reagente->setAttribute('lotes', $lotes);
        $reagente->setAttribute('proxima_validade', $lotes[0]['data_validade'] ?? null);
        return $reagente;
    }

    private function carregar(string $id): ?reagente
    {
        $reagente = reagente::comLotes()->find($id);
        return $reagente ? $this->comLotes($reagente) : null;
    }

    public function index()
    {
        $reagentes = reagente::comLotes()->orderBy('nome')->get()
            ->map(fn ($reagente) => $this->comLotes($reagente));
        return response()->json($reagentes, 200);
    }

    public function store(Request $request)
    {
        $dados = $request->validate($this->regras(), $this->mensagens());
        $dados['meses_alerta'] = $dados['meses_alerta'] ?? Configuracao::valor('meses_alerta_padrao');

        $reagente = DB::transaction(function () use ($dados, $request) {
            $reagente = reagente::create($dados);

            // Lote inicial: todas as unidades compartilham a mesma validade
            if ((float) $dados['quantidade'] > 0) {
                entrada_reagente::create([
                    'idreagente' => $reagente->idreagente,
                    'idlaboratorio' => $dados['idlaboratorio'],
                    'idusuario' => $request->user()->id,
                    'quantidade' => $dados['quantidade'],
                    'lote' => $dados['lote'],
                    'data_validade' => $dados['data_validade'],
                    'data' => now()->toDateString(),
                    'observacao' => trim($dados['origem'] ?? '') ?: 'Lote inicial (cadastro do reagente)',
                ]);
            }

            return $reagente;
        });

        return response()->json($this->carregar($reagente->idreagente), 201);
    }

    public function show(string $id)
    {
        $reagente = $this->carregar($id);
        if (!$reagente) {
            return response()->json(['message' => 'Reagente não encontrado'], 404);
        }
        return response()->json($reagente, 200);
    }

    /**
     * A quantidade é ignorada: o estoque muda apenas por entrada de lote ou registro de uso.
     */
    public function update(Request $request, string $id)
    {
        $reagente = reagente::find($id);
        if (!$reagente) {
            return response()->json(['message' => 'Reagente não encontrado'], 404);
        }

        $dados = $request->validate($this->regras((int) $reagente->idreagente), $this->mensagens());
        // O estoque está guardado nessa unidade: trocar kg por L mudaria o valor real sem converter nada
        if (isset($dados['idunidademedida']) && (int) $dados['idunidademedida'] !== (int) $reagente->idunidademedida && $reagente->entradas()->exists()) {
            return response()->json(['message' => 'A unidade não pode ser trocada: este reagente já tem lotes registrados nela.'], 422);
        }

        // Tirar um produto do controle esconderia o histórico da fiscalização: só o administrador pode
        if ($reagente->controlado && array_key_exists('controlado', $dados) && !$dados['controlado'] && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Apenas o administrador pode remover um produto do controle.'], 403);
        }

        if (array_key_exists('meses_alerta', $dados) && $dados['meses_alerta'] === null) {
            $dados['meses_alerta'] = Configuracao::valor('meses_alerta_padrao');
        }

        $reagente->update($dados);

        return response()->json($this->carregar($id), 200);
    }

    /**
     * Sem nenhum uso registrado (ex.: cadastro feito por engano), os lotes saem junto.
     * Com uso registrado, o histórico é preservado e a exclusão é recusada.
     */
    public function destroy(Request $request, string $id)
    {
        $reagente = reagente::find($id);
        if (!$reagente) {
            return response()->json(['message' => 'Reagente não encontrado'], 404);
        }

        if ($reagente->controlado && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Apenas o administrador pode excluir um produto controlado.'], 403);
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
