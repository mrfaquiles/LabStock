<?php

namespace App\Models;

use App\Models\Concerns\RegistraHistorico;
use Illuminate\Database\Eloquent\Model;

class saida_reagente extends Model
{
    use RegistraHistorico;

    // A movimentação já é o próprio registro; o histórico guarda edições e estornos
    protected array $historicoAcoes = ['alterado', 'excluido'];

    protected $table = 'saida_reagentes';
    protected $primaryKey = 'idsaidareagente';
    protected $fillable = [
        'idreagente',
        'identrada',
        'idusuario',
        'quantidade',
        'data',
        'observacao',
    ];

    public function reagente()
    {
        return $this->belongsTo(reagente::class, 'idreagente', 'idreagente');
    }

    public function entrada()
    {
        return $this->belongsTo(entrada_reagente::class, 'identrada', 'identradareagente');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idusuario', 'id');
    }

    // Alias genérico usado pelo MovimentacaoEstoqueController
    public function item()
    {
        return $this->reagente();
    }
}
