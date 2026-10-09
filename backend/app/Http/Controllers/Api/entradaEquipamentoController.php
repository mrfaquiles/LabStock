<?php

namespace App\Http\Controllers\Api;

use App\Models\entrada_equipamento;
use App\Models\equipamento;
use App\Models\equipamento_local;
use Illuminate\Database\Eloquent\Model;

/**
 * Registra a chegada de unidades novas de um equipamento em um laboratório.
 */
class entradaEquipamentoController extends MovimentacaoEstoqueController
{
    protected string $model = entrada_equipamento::class;
    protected string $itemModel = equipamento::class;
    protected string $itemChave = 'idequipamento';
    protected bool $dataComHora = true;
    protected string $nome = 'Entrada de equipamento';

    protected function regras(): array
    {
        return [
            'idequipamento' => 'required|exists:equipamentos,idequipamento',
            'idlaboratorio' => 'required|exists:laboratorios,idlaboratorio',
            'quantidade' => 'required|integer|min:1',
            'data' => 'nullable|date',
            'observacao' => 'nullable|string|max:255',
        ];
    }

    // As unidades novas ficam no laboratório informado
    protected function aposRegistrar(Model $movimentacao, Model $item): void
    {
        equipamento_local::doLocal($item->idequipamento, $movimentacao->idlaboratorio)
            ->increment('quantidade', $movimentacao->quantidade);
    }

    protected function aoEstornar(Model $movimentacao): ?string
    {
        $local = equipamento_local::doLocal($movimentacao->idequipamento, $movimentacao->idlaboratorio);
        if ($local->quantidade < $movimentacao->quantidade) {
            return 'Não é possível estornar: as unidades já foram transferidas ou baixadas deste laboratório.';
        }
        $local->decrement('quantidade', $movimentacao->quantidade);
        return null;
    }
}
