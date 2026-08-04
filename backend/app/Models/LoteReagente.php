<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoteReagente extends Model
{
    use HasFactory;

    protected $table = 'lotes_reagentes';
    protected $fillable = [
        'item_id', 
        'numero_lote', 
        'data_validade', 
        'quantidade_atual', 
        'local_armazenamento'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }
}