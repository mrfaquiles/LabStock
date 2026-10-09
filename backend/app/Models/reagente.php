<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class reagente extends Model
{
    use HasFactory;

    protected $table = 'reagentes';
    protected $primaryKey = 'idreagente';
    
    protected $fillable = [
        'nome',
        'descricao',
        'idunidademedida',
        'quantidade',
        'catmat',
        'lote',
        'data_validade',
        'localizacao',
        'meses_alerta',
        'imagem',
        'ativo',
    ];

    public function unidadeMedida()
    {
        return $this->belongsTo(unidade_medida::class, 'idunidademedida', 'idunidademedida');
    }

    public function entradas()
    {
        return $this->hasMany(entrada_reagente::class, 'idreagente', 'idreagente');
    }

    public function saidas()
    {
        return $this->hasMany(saida_reagente::class, 'idreagente', 'idreagente');
    }

    // Carrega os lotes já com o total consumido de cada um (evita uma consulta por lote)
    public function scopeComLotes($query)
    {
        return $query->with([
            'unidadeMedida',
            'entradas' => fn ($q) => $q->withSum('saidas', 'quantidade'),
        ]);
    }

    /**
     * Lotes com saldo disponível, do que vence primeiro para o último (FEFO).
     * Use com o scope comLotes().
     */
    public function lotesComSaldo(): array
    {
        return $this->entradas
            ->map(fn ($entrada) => [
                'identradareagente' => $entrada->identradareagente,
                'lote' => $entrada->lote,
                'data_validade' => $entrada->data_validade,
                'quantidade_entrada' => (float) $entrada->quantidade,
                'saldo' => round((float) $entrada->quantidade - (float) $entrada->saidas_sum_quantidade, 6),
            ])
            ->filter(fn ($lote) => $lote['saldo'] > 0)
            ->sortBy(fn ($lote) => $lote['data_validade'] ?? '9999-12-31')
            ->values()
            ->all();
    }
}