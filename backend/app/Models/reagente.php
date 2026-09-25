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
        return $this->hasMany(entrada_reagentes::class, 'idreagente', 'idreagente');
    }

    public function saidas()
    {
        return $this->hasMany(saida_reagentes::class, 'idreagente', 'idreagente');
    }
}