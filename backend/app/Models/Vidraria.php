<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vidraria extends Model
{
    protected $fillable = [
        'nome',
        'codigo',
        'capacidade',
        'quantidade',
        'estado',
        'localizacao'
    ];
}