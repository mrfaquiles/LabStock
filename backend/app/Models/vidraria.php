<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class vidraria extends Model
{
    //
    protected $table = 'vidrarias';
    protected $primaryKey = 'idvidraria';
    protected $fillable = [
        'nome',
        'descricao',
        'quantidade',
        'catmat',
        'idlaboratorio',
        'localizacao',
        'ativo',
    ];

    public function laboratorio()
    {
        return $this->belongsTo(laboratorio::class, 'idlaboratorio', 'idlaboratorio');
    }

    public function entradas()
    {
        return $this->hasMany(entrada_vidraria::class, 'idvidraria', 'idvidraria');
    }

    public function saidas()
    {
        return $this->hasMany(saida_vidraria::class, 'idvidraria', 'idvidraria');
    }
}
