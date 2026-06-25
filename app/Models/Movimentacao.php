<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Movimentacao extends Model
{
    protected $table = 'movimentacao';
    protected $primaryKey = 'id_movimentacao';
    public $timestamps = false; 

    // Campos liberados para inserção
    protected $fillable = [
        'id_item',
        'id_lote',
        'id_usuario',
        'tipo_movimentacao',
        'quantidade',
        'data_movimentacao',
        'motivo'
    ];
}