<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Número de patrimônio de uma unidade de equipamento.
 * baixado_em preenchido = a unidade saiu do estoque (o número fica no histórico).
 */
class equipamento_patrimonio extends Model
{
    protected $table = 'equipamento_patrimonios';
    protected $primaryKey = 'idpatrimonio';
    protected $fillable = ['idequipamento', 'numero', 'idlaboratorio', 'identrada', 'idsaida', 'baixado_em'];

    protected function casts(): array
    {
        return ['baixado_em' => 'datetime'];
    }

    public function equipamento()
    {
        return $this->belongsTo(equipamento::class, 'idequipamento', 'idequipamento');
    }

    // Onde a unidade está (nulo = sem local definido)
    public function laboratorio()
    {
        return $this->belongsTo(laboratorio::class, 'idlaboratorio', 'idlaboratorio');
    }

    public function scopeAtivos($query)
    {
        return $query->whereNull('baixado_em');
    }

    public function scopeNoLocal($query, ?int $idlaboratorio)
    {
        return $idlaboratorio ? $query->where('idlaboratorio', $idlaboratorio) : $query->whereNull('idlaboratorio');
    }

    /**
     * Confere os patrimônios escolhidos para sair de um local (transferência ou baixa).
     * Devolve os patrimônios ou a mensagem de erro.
     * Se todas as unidades que saem têm número, é preciso dizer quais são.
     */
    public static function escolherNoLocal(int $idequipamento, ?int $idlaboratorio, array $ids, int $quantidade, int $unidadesNoLocal): array|string
    {
        $doLocal = static::ativos()->where('idequipamento', $idequipamento)->noLocal($idlaboratorio);
        $escolhidos = (clone $doLocal)->whereIn('idpatrimonio', $ids)->get();

        if ($escolhidos->count() !== count($ids)) {
            return 'Um dos patrimônios escolhidos não está neste local ou já recebeu baixa.';
        }
        if ($ids && count($ids) !== $quantidade) {
            return 'A quantidade precisa ser igual ao número de patrimônios escolhidos (' . count($ids) . ').';
        }

        $semNumero = max(0, $unidadesNoLocal - $doLocal->count());
        if ($quantidade - count($ids) > $semNumero) {
            return 'Escolha quais patrimônios estão saindo deste local.';
        }

        return $escolhidos->all();
    }

    /** "IF-001, IF-002" para registrar na observação da movimentação. */
    public static function listar(array $patrimonios): string
    {
        return 'Patrimônio(s): ' . collect($patrimonios)->pluck('numero')->implode(', ');
    }

    /** Validação da lista de números enviada no cadastro ou na entrada. */
    public static function regras(): array
    {
        return [
            'patrimonios' => 'nullable|array|max:500',
            'patrimonios.*' => 'required|string|max:100|distinct:ignore_case|unique:equipamento_patrimonios,numero',
        ];
    }

    /** Tira espaços e itens vazios. */
    public static function limpar(array $numeros): array
    {
        return array_values(array_filter(array_map(fn ($n) => trim((string) $n), $numeros), fn ($n) => $n !== ''));
    }
}
