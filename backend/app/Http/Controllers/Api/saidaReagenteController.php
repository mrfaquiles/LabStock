<?php

namespace App\Http\Controllers\Api;

use App\Models\entrada_reagente;
use App\Models\reagente;
use App\Models\saida_reagente;
use Illuminate\Database\Eloquent\Model;

/**
 * Registro de uso de reagente: quem usou informa quanto gastou (na unidade base,
 * ex.: 500 g = 0.5 kg) e de qual lote saiu. O valor é descontado do saldo do lote
 * e do estoque total do reagente.
 */
class saidaReagenteController extends MovimentacaoEstoqueController
{
    protected string $model = saida_reagente::class;
    protected string $itemModel = reagente::class;
    protected string $itemChave = 'idreagente';
    protected bool $saida = true;
    protected string $nome = 'Saída de reagente';
    protected array $relacoes = ['usuario:id,nome,email', 'entrada'];

    protected function regras(): array
    {
        return [
            'idreagente' => 'required|exists:reagentes,idreagente',
            'identrada' => 'required|exists:entrada_reagentes,identradareagente',
            'quantidade' => 'required|numeric|gt:0',
            'data' => 'nullable|date',
            'observacao' => 'nullable|string|max:255',
        ];
    }

    protected function validarNegocio(array $dados, Model $item): ?string
    {
        // Trava o lote junto com o reagente para o saldo não ser consumido duas vezes
        $entrada = entrada_reagente::lockForUpdate()->find($dados['identrada']);

        if ((int) $entrada->idreagente !== (int) $item->idreagente) {
            return 'O lote informado não pertence a este reagente.';
        }

        $saldo = $entrada->saldo();
        if ((float) $dados['quantidade'] - $saldo > 0.0000001) {
            $unidade = $item->unidadeMedida?->sigla ?? '';
            return "O lote {$entrada->lote} tem apenas {$saldo} {$unidade} disponível.";
        }

        return null;
    }
}
