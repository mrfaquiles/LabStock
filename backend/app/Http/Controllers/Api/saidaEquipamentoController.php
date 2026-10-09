<?php

namespace App\Http\Controllers\Api;

use App\Models\equipamento;
use App\Models\equipamento_local;
use App\Models\saida_equipamento;
use Illuminate\Database\Eloquent\Model;

/**
 * Baixa de equipamento (defeito sem conserto, doação, baixa patrimonial...).
 * Sai do laboratório onde está; o motivo é obrigatório.
 * Para mudar de lugar, use a transferência (equipamentos/{id}/transferir).
 */
class saidaEquipamentoController extends MovimentacaoEstoqueController
{
    protected string $model = saida_equipamento::class;
    protected string $itemModel = equipamento::class;
    protected string $itemChave = 'idequipamento';
    protected bool $saida = true;
    protected string $nome = 'Baixa de equipamento';

    protected function regras(): array
    {
        return [
            'idequipamento' => 'required|exists:equipamentos,idequipamento',
            // nulo = baixa de unidades que estão "sem local definido"
            'idlaboratorio' => 'nullable|exists:laboratorios,idlaboratorio',
            'quantidade' => 'required|integer|min:1',
            'data' => 'nullable|date',
            'observacao' => 'required|string|min:5|max:255',
        ];
    }

    protected function validarNegocio(array $dados, Model $item): ?string
    {
        $local = equipamento_local::doLocal($item->idequipamento, $dados['idlaboratorio'] ?? null);
        if ($local->quantidade < $dados['quantidade']) {
            return "Há apenas {$local->quantidade} unidade(s) deste equipamento no local escolhido.";
        }
        return null;
    }

    protected function aposRegistrar(Model $movimentacao, Model $item): void
    {
        equipamento_local::doLocal($item->idequipamento, $movimentacao->idlaboratorio)
            ->decrement('quantidade', $movimentacao->quantidade);
    }

    protected function aoEstornar(Model $movimentacao): ?string
    {
        equipamento_local::doLocal($movimentacao->idequipamento, $movimentacao->idlaboratorio)
            ->increment('quantidade', $movimentacao->quantidade);
        return null;
    }
}
