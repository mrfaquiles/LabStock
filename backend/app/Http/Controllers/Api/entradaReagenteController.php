<?php

namespace App\Http\Controllers\Api;

use App\Models\entrada_reagente;
use App\Models\reagente;

/**
 * Registra a chegada de um novo lote de reagente. Todas as unidades do lote
 * compartilham a mesma validade. Quantidade sempre na unidade base (kg, L, un),
 * aceitando frações decimais (ex.: 0.5 kg).
 */
class entradaReagenteController extends MovimentacaoEstoqueController
{
    protected string $model = entrada_reagente::class;
    protected string $itemModel = reagente::class;
    protected string $itemChave = 'idreagente';
    protected string $nome = 'Entrada de reagente';

    protected function regras(): array
    {
        return [
            'idreagente' => 'required|exists:reagentes,idreagente',
            'idlaboratorio' => 'nullable|exists:laboratorios,idlaboratorio',
            'quantidade' => 'required|numeric|gt:0|max:9999999',
            'lote' => 'required|string|max:255',
            'data_validade' => 'required|date',
            'data' => 'nullable|date',
            'observacao' => 'nullable|string|max:255',
        ];
    }
}
