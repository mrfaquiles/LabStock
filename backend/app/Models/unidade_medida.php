<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class unidade_medida extends Model
{
    //
    protected $table = 'unidade_medidas';
    protected $primaryKey = 'idunidademedida';
    protected $fillable = [
        'nome',
        'descricao',
    ];

}
