<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $table = 'itens'; // Nome da tabela que criamos
    protected $primaryKey = 'id';
    public $timestamps = true; // Para gerenciar created_at e updated_at

    // Campos liberados para o Laravel salvar no banco
    protected $fillable = [
        'codigo_cat',
        'nome_curto',
        'descricao_longa',
        'categoria',
        'unidade_medida_id'
    ];

    // Relacionamentos
    public function unidadeMedida()
    {
        return $this->belongsTo(UnidadeMedida::class, 'unidade_medida_id');
    }

    public function lotes()
    {
        return $this->hasMany(LoteReagente::class, 'item_id');
    }

    public function movimentacoes()
    {
        return $this->hasMany(Movimentacao::class, 'item_id');
    }
}