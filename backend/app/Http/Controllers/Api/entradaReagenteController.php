<?php

namespace App\Http\Controllers\Api;

use App\Models\entrada_reagente;
use App\Models\reagente;
use Illuminate\Database\Eloquent\Model;

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
            'idlaboratorio' => 'required|exists:laboratorios,idlaboratorio',
            'quantidade' => 'required|numeric|min:0.000001|max:9999999',
            'lote' => 'required|string|max:255',
            'data_validade' => 'required|date',
            'data' => 'nullable|date',
            'observacao' => 'nullable|string|max:255',
        ];
    }

    protected function mensagens(): array
    {
        return [
            'quantidade.min' => 'A quantidade mínima é de 1 mg ou 1 µL.',
            'idlaboratorio.required' => 'Informe o laboratório onde o lote foi guardado.',
        ];
    }

    // Correção de número ou validade digitados errado (fica no histórico)
    protected function regrasEdicao(): array
    {
        return parent::regrasEdicao() + [
            'lote' => 'sometimes|required|string|max:255',
            'data_validade' => 'sometimes|required|date',
        ];
    }

    // Produto controlado: é preciso explicar de onde veio o material
    protected function validarNegocio(array $dados, Model $item): ?string
    {
        if ($item->controlado && mb_strlen(trim($dados['observacao'] ?? '')) < 5) {
            return 'Produto controlado: informe a origem da entrada (ex.: compra, nota fiscal, doação).';
        }
        return null;
    }

    protected function aoEstornar(Model $movimentacao): ?string
    {
        return $movimentacao->reagente?->controlado
            ? 'Registros de produto controlado não podem ser apagados. Para corrigir um erro, registre uma nova movimentação explicando a correção.'
            : null;
    }

    protected function aoEditar(Model $movimentacao): ?string
    {
        return $movimentacao->reagente?->controlado
            ? 'A observação de produto controlado não pode ser alterada depois de registrada.'
            : null;
    }
}
