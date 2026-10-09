<?php

namespace App\Http\Controllers\Api;

use App\Models\entrada_equipamento;
use App\Models\equipamento;
use App\Models\laboratorio;
use Illuminate\Database\Eloquent\Model;

/**
 * Registra a chegada de um equipamento em um laboratório.
 * A localização atual do equipamento passa a ser esse laboratório.
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

    protected function aposRegistrar(Model $movimentacao, Model $item): void
    {
        $laboratorio = laboratorio::find($movimentacao->idlaboratorio);
        // A observação pode detalhar a bancada (ex.: "Bancada 03")
        $item->localizacao = $movimentacao->observacao
            ? "{$laboratorio->nome} - {$movimentacao->observacao}"
            : $laboratorio->nome;
        $item->save();
    }
}
