<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class entrada_reagente extends Model
{
    //
    protected $table = 'entrada_reagentes';
    protected $primaryKey = 'identradareagente';
    protected $fillable = [
        'idreagente',
        'idlaboratorio',
        'idusuario',
        'quantidade',
        'lote',
        'data_validade',
        'data',
        'observacao',
    ];

    public function reagente()
    {
        return $this->belongsTo(reagente::class, 'idreagente', 'idreagente');
    }

    public function laboratorio()
    {
        return $this->belongsTo(laboratorio::class, 'idlaboratorio', 'idlaboratorio');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idusuario', 'id');
    }

    // Consumos descontados deste lote
    public function saidas()
    {
        return $this->hasMany(saida_reagente::class, 'identrada', 'identradareagente');
    }

    // Quanto ainda resta deste lote
    public function saldo(): float
    {
        return round((float) $this->quantidade - (float) $this->saidas()->sum('quantidade'), 3);
    }

    // Alias genérico usado pelo MovimentacaoEstoqueController
    public function item()
    {
        return $this->reagente();
    }
}
