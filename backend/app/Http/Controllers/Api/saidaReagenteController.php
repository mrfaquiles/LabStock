<?php

namespace App\Http\Controllers\Api;

use App\Models\entrada_reagente;
use App\Models\reagente;
use App\Models\saida_reagente;
use App\Support\Quantidade;
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
            'quantidade' => 'required|numeric|min:0.000001',
            'data' => 'nullable|date',
            'observacao' => 'nullable|string|max:255',
            // Quem usa um lote vencido precisa confirmar que está ciente
            'confirmar_vencido' => 'boolean',
        ];
    }

    protected function mensagens(): array
    {
        return ['quantidade.min' => 'A quantidade mínima é de 1 mg ou 1 µL.'];
    }

    protected function textoQuantidade(float $valor, Model $item): string
    {
        return Quantidade::formatar($valor, $item->unidadeMedida?->sigla);
    }

    protected function validarNegocio(array $dados, Model $item): ?string
    {
        if ($item->controlado && mb_strlen(trim($dados['observacao'] ?? '')) < 10) {
            return 'Produto controlado: descreva a justificativa de uso com pelo menos 10 caracteres.';
        }

        // Trava o lote junto com o reagente para o saldo não ser consumido duas vezes
        $entrada = entrada_reagente::lockForUpdate()->find($dados['identrada']);

        if ((int) $entrada->idreagente !== (int) $item->idreagente) {
            return 'O lote informado não pertence a este reagente.';
        }

        // O uso não pode ser anterior à chegada do lote
        $dataUso = substr((string) ($dados['data'] ?? now()->toDateString()), 0, 10);
        $dataEntrada = substr((string) $entrada->data, 0, 10);
        if ($dataUso < $dataEntrada) {
            return 'A data do uso não pode ser anterior à entrada do lote ' . $entrada->lote
                . ' (' . date('d/m/Y', strtotime($dataEntrada)) . ').';
        }

        // Lote vencido: permitido, mas só com confirmação explícita
        $validade = substr((string) $entrada->data_validade, 0, 10);
        if ($validade && $validade < $dataUso && empty($dados['confirmar_vencido'])) {
            return 'O lote ' . $entrada->lote . ' está vencido desde ' . date('d/m/Y', strtotime($validade))
                . '. Confirme que deseja utilizá-lo mesmo assim.';
        }

        $saldo = $entrada->saldo();
        if ((float) $dados['quantidade'] - $saldo > 0.0000001) {
            $disponivel = Quantidade::formatar($saldo, $item->unidadeMedida?->sigla);
            return "O lote {$entrada->lote} tem apenas {$disponivel} disponível.";
        }

        return null;
    }

    // O registro de uso de produto controlado é a prova em uma fiscalização: não muda depois de gravado
    protected function aoEstornar(Model $movimentacao): ?string
    {
        return $movimentacao->reagente?->controlado
            ? 'Registros de produto controlado não podem ser apagados. Para corrigir um erro, registre uma nova entrada explicando a correção.'
            : null;
    }

    protected function aoEditar(Model $movimentacao): ?string
    {
        return $movimentacao->reagente?->controlado
            ? 'A justificativa de produto controlado não pode ser alterada depois de registrada.'
            : null;
    }
}
