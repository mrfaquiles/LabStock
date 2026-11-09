<?php

namespace App\Http\Controllers\Api;

use App\Models\entrada_equipamento;
use App\Models\equipamento;
use App\Models\equipamento_local;
use App\Models\equipamento_patrimonio;
use Illuminate\Database\Eloquent\Model;

/**
 * Registra a chegada de unidades novas de um equipamento em um laboratório.
 * Com números de patrimônio, cada número é uma unidade (a quantidade é o total de números).
 */
class entradaEquipamentoController extends MovimentacaoEstoqueController
{
    protected string $model = entrada_equipamento::class;
    protected string $itemModel = equipamento::class;
    protected string $itemChave = 'idequipamento';
    protected bool $dataComHora = true;
    protected string $nome = 'Entrada de equipamento';

    /** Números recebidos nesta entrada, gravados depois que ela é criada */
    private array $patrimoniosNovos = [];

    protected function regras(): array
    {
        return [
            'idequipamento' => 'required|exists:equipamentos,idequipamento',
            'idlaboratorio' => 'required|exists:laboratorios,idlaboratorio',
            'quantidade' => 'required|integer|min:1|max:100000',
            'data' => 'nullable|date',
            'observacao' => 'nullable|string|max:255',
        ] + equipamento_patrimonio::regras();
    }

    protected function validarNegocio(array $dados, Model $item): ?string
    {
        $patrimonios = equipamento_patrimonio::limpar($dados['patrimonios'] ?? []);
        if ($patrimonios && count($patrimonios) !== (int) $dados['quantidade']) {
            return 'A quantidade precisa ser igual ao número de patrimônios informados (' . count($patrimonios) . ').';
        }
        return null;
    }

    protected function prepararDados(array $dados, Model $item): array
    {
        $this->patrimoniosNovos = equipamento_patrimonio::limpar($dados['patrimonios'] ?? []);

        // Os números ficam também na observação, para aparecerem no histórico
        if ($this->patrimoniosNovos) {
            $lista = 'Patrimônio(s): ' . implode(', ', $this->patrimoniosNovos);
            $dados['observacao'] = mb_substr(trim(($dados['observacao'] ?? '') . ($dados['observacao'] ?? '' ? ' — ' : '') . $lista), 0, 255);
        }
        unset($dados['patrimonios']);
        return $dados;
    }

    // As unidades novas ficam no laboratório informado
    protected function aposRegistrar(Model $movimentacao, Model $item): void
    {
        equipamento_local::doLocal($item->idequipamento, $movimentacao->idlaboratorio)
            ->increment('quantidade', $movimentacao->quantidade);

        foreach ($this->patrimoniosNovos as $numero) {
            equipamento_patrimonio::create([
                'idequipamento' => $item->idequipamento,
                'numero' => $numero,
                'idlaboratorio' => $movimentacao->idlaboratorio,
                'identrada' => $movimentacao->identradaequipamento,
            ]);
        }
    }

    protected function aoEstornar(Model $movimentacao): ?string
    {
        $local = equipamento_local::doLocal($movimentacao->idequipamento, $movimentacao->idlaboratorio);
        if ($local->quantidade < $movimentacao->quantidade) {
            return 'Não é possível estornar: as unidades já foram transferidas ou baixadas deste laboratório.';
        }

        $patrimonios = equipamento_patrimonio::where('identrada', $movimentacao->identradaequipamento);
        if ((clone $patrimonios)->whereNotNull('baixado_em')->exists()) {
            return 'Não é possível estornar: um dos patrimônios desta entrada já recebeu baixa.';
        }
        if ((clone $patrimonios)->where(fn ($q) => $q->where('idlaboratorio', '<>', $movimentacao->idlaboratorio)->orWhereNull('idlaboratorio'))->exists()) {
            return 'Não é possível estornar: um dos patrimônios desta entrada já foi transferido para outro local.';
        }

        // Patrimônios sem ligação com esta entrada (cadastros antigos) não podem ficar sem unidade
        $outrosNoLocal = equipamento_patrimonio::ativos()
            ->where('idequipamento', $movimentacao->idequipamento)
            ->noLocal($movimentacao->idlaboratorio)
            ->where(fn ($q) => $q->whereNull('identrada')->orWhere('identrada', '<>', $movimentacao->identradaequipamento))
            ->count();
        if ($outrosNoLocal > $local->quantidade - $movimentacao->quantidade) {
            return 'Não é possível estornar: há patrimônios neste local que ficariam sem unidade. Dê baixa no patrimônio correspondente.';
        }

        $local->decrement('quantidade', $movimentacao->quantidade);
        $patrimonios->delete();
        return null;
    }
}
