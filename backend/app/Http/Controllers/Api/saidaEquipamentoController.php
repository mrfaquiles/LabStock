<?php

namespace App\Http\Controllers\Api;

use App\Models\equipamento;
use App\Models\equipamento_local;
use App\Models\equipamento_patrimonio;
use App\Models\saida_equipamento;
use Illuminate\Database\Eloquent\Model;

/**
 * Baixa de equipamento (defeito sem conserto, doação, baixa patrimonial...).
 * Sai do laboratório onde está; o motivo é obrigatório.
 * Se o equipamento tem patrimônios, informa quais unidades estão saindo.
 * Para mudar de lugar, use a transferência (equipamentos/{id}/transferir).
 */
class saidaEquipamentoController extends MovimentacaoEstoqueController
{
    protected string $model = saida_equipamento::class;
    protected string $itemModel = equipamento::class;
    protected string $itemChave = 'idequipamento';
    protected bool $saida = true;
    protected string $nome = 'Baixa de equipamento';

    /** Patrimônios que saem nesta baixa */
    private array $patrimoniosBaixa = [];

    protected function regras(): array
    {
        return [
            'idequipamento' => 'required|exists:equipamentos,idequipamento',
            // nulo = baixa de unidades que estão "sem local definido"
            'idlaboratorio' => 'nullable|exists:laboratorios,idlaboratorio',
            'quantidade' => 'required|integer|min:1',
            'data' => 'nullable|date',
            'observacao' => 'required|string|min:5|max:255',
            'patrimonios_baixa' => 'nullable|array',
            'patrimonios_baixa.*' => 'integer|distinct|exists:equipamento_patrimonios,idpatrimonio',
        ];
    }

    protected function validarNegocio(array $dados, Model $item): ?string
    {
        $local = equipamento_local::doLocal($item->idequipamento, $dados['idlaboratorio'] ?? null);
        if ($local->quantidade < $dados['quantidade']) {
            return "Há apenas {$local->quantidade} unidade(s) deste equipamento no local escolhido.";
        }

        // Os patrimônios precisam estar no local de onde a unidade sai
        $escolhidos = equipamento_patrimonio::escolherNoLocal(
            $item->idequipamento, $dados['idlaboratorio'] ?? null,
            $dados['patrimonios_baixa'] ?? [], (int) $dados['quantidade'], (int) $local->quantidade
        );
        if (is_string($escolhidos)) {
            return $escolhidos;
        }

        $this->patrimoniosBaixa = $escolhidos;
        return null;
    }

    protected function prepararDados(array $dados, Model $item): array
    {
        if ($this->patrimoniosBaixa) {
            $dados['observacao'] = mb_substr($dados['observacao'] . ' — ' . equipamento_patrimonio::listar($this->patrimoniosBaixa), 0, 255);
        }
        unset($dados['patrimonios_baixa']);
        return $dados;
    }

    protected function aposRegistrar(Model $movimentacao, Model $item): void
    {
        equipamento_local::doLocal($item->idequipamento, $movimentacao->idlaboratorio)
            ->decrement('quantidade', $movimentacao->quantidade);

        foreach ($this->patrimoniosBaixa as $patrimonio) {
            $patrimonio->update(['baixado_em' => now(), 'idsaida' => $movimentacao->idsaidaequipamento]);
        }
    }

    protected function aoEstornar(Model $movimentacao): ?string
    {
        equipamento_local::doLocal($movimentacao->idequipamento, $movimentacao->idlaboratorio)
            ->increment('quantidade', $movimentacao->quantidade);

        // Os patrimônios voltam ao estoque
        equipamento_patrimonio::where('idsaida', $movimentacao->idsaidaequipamento)
            ->update(['baixado_em' => null, 'idsaida' => null]);
        return null;
    }
}
