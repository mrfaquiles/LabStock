<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class equipamento extends Model
{
    //
    protected $table = 'equipamentos';
    protected $primaryKey = 'idequipamento';
    protected $fillable = [
        'nome',
        'descricao',
        'quantidade',
        'catmat',
        'ativo',
    ];

    public function entradas()
    {
        return $this->hasMany(entrada_equipamentos::class, 'idequipamento', 'idequipamento');
    }

    public function saidas()
    {
        return $this->hasMany(saida_equipamentos::class, 'idequipamento', 'idequipamento');
    }
}
