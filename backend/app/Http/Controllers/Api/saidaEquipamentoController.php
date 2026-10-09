<?php

namespace App\Http\Controllers\Api;

use App\Models\equipamento;
use App\Models\saida_equipamento;

/**
 * Registra a saída de um equipamento de um laboratório
 * (transferência, envio para manutenção, baixa patrimonial).
 */
class saidaEquipamentoController extends MovimentacaoEstoqueController
{
    protected string $model = saida_equipamento::class;
    protected string $itemModel = equipamento::class;
    protected string $itemChave = 'idequipamento';
    protected bool $saida = true;
    protected string $nome = 'Saída de equipamento';

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
}
