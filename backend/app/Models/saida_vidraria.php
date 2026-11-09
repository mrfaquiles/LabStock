<?php

namespace App\Models;

use App\Models\Concerns\RegistraHistorico;
use Illuminate\Database\Eloquent\Model;

/**
 * Solicitação de baixa de vidraria (quebra, perda, descarte).
 * A justificativa fica em `observacao`. O estoque só é descontado quando aprovada.
 */
class saida_vidraria extends Model
{
    use RegistraHistorico;

    // A movimentação já é o próprio registro; o histórico guarda edições e estornos
    protected array $historicoAcoes = ['alterado', 'excluido'];

    public const PENDENTE = 'pendente';
    public const APROVADA = 'aprovada';
    public const RECUSADA = 'recusada';

    public const MOTIVOS = ['Quebra', 'Perda', 'Defeito', 'Descarte', 'Outro'];

    protected $table = 'saida_vidrarias';
    protected $primaryKey = 'idsaidavidraria';
    protected $fillable = [
        'idvidraria',
        'idlaboratorio',
        'idusuario',
        'idaprovador',
        'quantidade',
        'status',
        'motivo',
        'data',
        'data_decisao',
        'observacao',
        'motivo_recusa',
    ];

    public function vidraria()
    {
        return $this->belongsTo(vidraria::class, 'idvidraria', 'idvidraria');
    }

    public function laboratorio()
    {
        return $this->belongsTo(laboratorio::class, 'idlaboratorio', 'idlaboratorio');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idusuario', 'id');
    }

    public function aprovador()
    {
        return $this->belongsTo(User::class, 'idaprovador', 'id');
    }

    // Alias genérico usado pelo MovimentacaoEstoqueController
    public function item()
    {
        return $this->vidraria();
    }

    // Só baixas aprovadas saíram do estoque (usado em relatórios e dashboard)
    public function scopeAprovadas($query)
    {
        return $query->where('status', self::APROVADA);
    }
}
