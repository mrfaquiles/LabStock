<?php

namespace App\Models;

use App\Models\Concerns\RegistraHistorico;
use Illuminate\Database\Eloquent\Model;

class entrada_vidraria extends Model
{
    use RegistraHistorico;

    // A movimentação já é o próprio registro; o histórico guarda edições e estornos
    protected array $historicoAcoes = ['alterado', 'excluido'];

    protected $table = 'entrada_vidrarias';
    protected $primaryKey = 'identradavidraria';
    protected $fillable = [
        'idvidraria',
        'idlaboratorio',
        'idusuario',
        'quantidade',
        'data',
        'observacao',
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

    // Alias genérico usado pelo MovimentacaoEstoqueController
    public function item()
    {
        return $this->vidraria();
    }
}
