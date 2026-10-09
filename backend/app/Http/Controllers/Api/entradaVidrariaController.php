<?php

namespace App\Http\Controllers\Api;

use App\Models\entrada_vidraria;
use App\Models\vidraria;

class entradaVidrariaController extends MovimentacaoEstoqueController
{
    protected string $model = entrada_vidraria::class;
    protected string $itemModel = vidraria::class;
    protected string $itemChave = 'idvidraria';
    protected bool $dataComHora = true;
    protected string $nome = 'Entrada de vidraria';

    protected function regras(): array
    {
        return [
            'idvidraria' => 'required|exists:vidrarias,idvidraria',
            'idlaboratorio' => 'required|exists:laboratorios,idlaboratorio',
            'quantidade' => 'required|integer|min:1',
            'data' => 'nullable|date',
            'observacao' => 'nullable|string|max:255',
        ];
    }
}
