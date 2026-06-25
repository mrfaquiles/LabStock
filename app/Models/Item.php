<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $table = 'item';
    protected $primaryKey = 'id_item';
    public $timestamps = false;

    // Campos liberados para o Laravel salvar no banco
    protected $fillable = [
        'nome',
        'tipo_item',
        'descricao_resumida',
        'descricao_detalhada',
        'unidade_medida',
        'catmat',
        'ativo'
    ];
}