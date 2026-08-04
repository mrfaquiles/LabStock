<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnidadeMedida extends Model
{
    use HasFactory;

    protected $table = 'unidades_medida';
    protected $fillable = ['sigla', 'descricao'];

    public function itens()
    {
        return $this->hasMany(Item::class, 'unidade_medida_id');
    }
}