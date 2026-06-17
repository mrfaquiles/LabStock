<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Laboratorio extends Model
{
    protected $table = 'laboratorio';
    protected $primaryKey = 'id_laboratorio';
    public $timestamps = false;

    protected $fillable = [
        'nome',
        'descricao'
    ];
}