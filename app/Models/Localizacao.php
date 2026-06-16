<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Localizacao extends Model
{
    protected $table = 'localizacao';
    protected $primaryKey = 'id_localizacao';
    public $timestamps = false;

    protected $fillable = [
        'id_laboratorio',
        'sala',
        'armario',
        'prateleira',
        'descricao'
    ];
}