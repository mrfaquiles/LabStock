<?php

namespace App\Models;

use App\Models\Concerns\RegistraHistorico;
use Illuminate\Database\Eloquent\Model;

class entrada_equipamento extends Model
{
    use RegistraHistorico;

    // A movimentação já é o próprio registro; o histórico guarda edições e estornos
    protected array $historicoAcoes = ['alterado', 'excluido'];

    protected $table = 'entrada_equipamentos';
    protected $primaryKey = 'identradaequipamento';
    protected $fillable = [
        'idequipamento',
        'idlaboratorio',
        'idusuario',
        'quantidade',
        'data',
        'observacao',
    ];

    public function equipamento()
    {
        return $this->belongsTo(equipamento::class, 'idequipamento', 'idequipamento');
    }

    public function laboratorio()
    {
        return $this->belongsTo(laboratorio::class, 'idlaboratorio', 'idlaboratorio');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idusuario', 'id');
    }

    // Alias genérico usado pelo MovimentacaoEstoqueController
    public function item()
    {
        return $this->equipamento();
    }
}
