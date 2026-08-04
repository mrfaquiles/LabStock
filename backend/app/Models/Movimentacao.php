<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movimentacao extends Model
{
    use HasFactory;

    protected $table = 'movimentacoes'; // Nome da tabela que criamos na migration
    protected $primaryKey = 'id';
    public $timestamps = true;

    // Campos liberados para inserção
    protected $fillable = [
        'item_id', 
        'user_id', 
        'tipo_movimentacao', 
        'quantidade_movimentada', 
        'motivo_ou_destino', 
        'status_aprovacao', 
        'data_devolucao'
    ];

    // Relação com o Item movimentado
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    // Relação com o Usuário que fez a solicitação/retirada
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}