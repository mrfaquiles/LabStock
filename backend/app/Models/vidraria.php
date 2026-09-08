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
        'ativo',
    ];

    public function entradas()
    {
        return $this->hasMany(entrada_vidrarias::class, 'idvidraria', 'idvidraria');
    }

    public function saidas()
    {
        return $this->hasMany(saida_vidrarias::class, 'idvidraria', 'idvidraria');
    }
}
