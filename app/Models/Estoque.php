<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Estoque extends Model
{
    protected $table = 'estoque';
    protected $primaryKey = 'id_estoque';
    public $timestamps = false;

    // Campos autorizados para salvar no banco de dados
    protected $fillable = [
        'id_item',
        'id_localizacao',
        'quantidade_total'
    ];
}