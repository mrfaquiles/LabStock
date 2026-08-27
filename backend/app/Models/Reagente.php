<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reagente extends Model
{
    use HasFactory;

    protected $table = 'reagentes';

    protected $fillable = [
        'nome',
        'formula_quimica',
        'cas_number'
    ];
}