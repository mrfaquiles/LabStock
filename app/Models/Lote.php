<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Lote extends Model
{
    protected $table = 'lote';
    protected $primaryKey = 'id_lote';
    public $timestamps = false;

    protected $fillable = [
        'id_item',
        'numero_lote',
        'data_validade',
        'data_entrada',
        'quantidade_inicial',
        'quantidade_atual'
    ];
}