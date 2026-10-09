<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Base das entradas e saídas de estoque (reagentes, vidrarias e equipamentos).
 *
 * Cada movimentação fica registrada como histórico e ajusta a quantidade do item
 * na mesma transação. O usuário responsável é sempre o autenticado.
 * Na edição só a observação pode mudar; quantidades e datas ficam preservadas.
 */
abstract class MovimentacaoEstoqueController extends Controller
{
    /** Model da movimentação (ex.: saida_vidraria::class) */
    protected string $model;

    /** Model do item movimentado (ex.: vidraria::class) */
    protected string $itemModel;

    /** Coluna que liga a movimentação ao item (ex.: 'idvidraria') */
    protected string $itemChave;

    /** true = saída (diminui o estoque), false = entrada (aumenta) */
    protected bool $saida = false;

    /** Coluna `data` do tipo datetime (guarda a hora) */
    protected bool $dataComHora = false;

    /** Nome usado nas mensagens (ex.: 'Saída de vidraria') */
    protected string $nome;

    protected array $relacoes = ['usuario:id,nome,email', 'laboratorio'];

    abstract protected function regras(): array;

    /** Validações extras de negócio. Retorna a mensagem de erro ou null. */
    protected function validarNegocio(array $dados, Model $item): ?string
    {
        return null;
    }

    /** Ajustes nos dados antes de gravar. */
    protected function prepararDados(array $dados, Model $item): array
    {
        return $dados;
    }

    /** Quantidade escrita nas mensagens (reagentes usam a unidade mais legível: g, mg, mL...). */
    protected function textoQuantidade(float $valor, Model $item): string
    {
        return rtrim(rtrim(number_format($valor, 6, ',', '.'), '0'), ',') . ' un';
    }

    /** Ações após registrar (ex.: atualizar a localização do equipamento). */
    protected function aposRegistrar(Model $movimentacao, Model $item): void
    {
    }

    /**
     * Validação/ajustes ao estornar (ex.: devolver o equipamento ao laboratório).
     * Retorna a mensagem de erro ou null.
     */
    protected function aoEstornar(Model $movimentacao): ?string
    {
        return null;
    }

    /**
     * Lista o histórico, do mais recente para o mais antigo.
     * Filtro opcional pelo item: ?idvidraria=3
     */
    public function index(Request $request)
    {
        $consulta = $this->model::with(array_merge($this->relacoes, ['item']))
            ->orderByDesc('data')
            ->orderByDesc('created_at');

        if ($request->filled($this->itemChave)) {
            $consulta->where($this->itemChave, $request->input($this->itemChave));
        }

        return response()->json($consulta->get(), 200);
    }

    public function store(Request $request)
    {
        $dados = $request->validate($this->regras());

        return DB::transaction(function () use ($request, $dados) {
            // Trava o item para evitar duas baixas simultâneas do mesmo estoque
            $item = $this->itemModel::lockForUpdate()->find($dados[$this->itemChave]);

            // Tolerância mínima para arredondamento de casas decimais (ex.: 0,1 + 0,2)
            if ($this->saida && (float) $dados['quantidade'] - (float) $item->quantidade > 1e-9) {
                return response()->json([
                    'message' => 'Quantidade indisponível em estoque. Disponível: ' . $this->textoQuantidade((float) $item->quantidade, $item),
                ], 422);
            }

            if ($erro = $this->validarNegocio($dados, $item)) {
                return response()->json(['message' => $erro], 422);
            }

            $dados = $this->prepararDados($dados, $item);
            $dados['idusuario'] = $request->user()->id;
            $dados['data'] = $dados['data'] ?? ($this->dataComHora ? now() : now()->toDateString());

            $movimentacao = $this->model::create($dados);
            $this->saida
                ? $item->decrement('quantidade', $dados['quantidade'])
                : $item->increment('quantidade', $dados['quantidade']);

            $this->aposRegistrar($movimentacao, $item);

            return response()->json($movimentacao->load(array_merge($this->relacoes, ['item'])), 201);
        });
    }

    public function show(string $id)
    {
        $movimentacao = $this->model::with(array_merge($this->relacoes, ['item']))->find($id);
        if (!$movimentacao) {
            return response()->json(['message' => "{$this->nome} não encontrada"], 404);
        }
        return response()->json($movimentacao, 200);
    }

    public function update(Request $request, string $id)
    {
        $movimentacao = $this->model::find($id);
        if (!$movimentacao) {
            return response()->json(['message' => "{$this->nome} não encontrada"], 404);
        }

        $dados = $request->validate([
            'observacao' => 'nullable|string|max:255',
        ]);

        $movimentacao->update($dados);
        return response()->json($movimentacao->load(array_merge($this->relacoes, ['item'])), 200);
    }

    /**
     * Estorna a movimentação (para corrigir lançamentos errados), devolvendo o estoque.
     */
    public function destroy(string $id)
    {
        $movimentacao = $this->model::find($id);
        if (!$movimentacao) {
            return response()->json(['message' => "{$this->nome} não encontrada"], 404);
        }

        return DB::transaction(function () use ($movimentacao) {
            $item = $this->itemModel::lockForUpdate()->find($movimentacao->{$this->itemChave});

            if (!$this->saida && (float) $movimentacao->quantidade > (float) $item->quantidade) {
                return response()->json([
                    'message' => 'Não é possível estornar: parte desta entrada já saiu do estoque.',
                ], 422);
            }

            if ($erro = $this->aoEstornar($movimentacao)) {
                return response()->json(['message' => $erro], 422);
            }

            $this->saida
                ? $item->increment('quantidade', $movimentacao->quantidade)
                : $item->decrement('quantidade', $movimentacao->quantidade);

            $movimentacao->delete();
            return response()->json(['message' => "{$this->nome} estornada com sucesso"], 200);
        });
    }
}
