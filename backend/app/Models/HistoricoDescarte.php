<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class HistoricoDescarte extends Model
{
    protected $table = 'historico_descarte';
    protected $primaryKey = 'id_descarte'; // Ajuste se a chave primária tiver outro nome
    public $timestamps = false;

    // Ajuste as colunas conforme o seu banco de dados
    protected $fillable = [
        'id_item',
        'id_usuario',
        'quantidade',
        'motivo',
        'data_descarte'
    ];
}