<?php

namespace App\Models;

use App\Models\Concerns\RegistraHistorico;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class equipamento extends Model
{
    use HasFactory, RegistraHistorico;

    protected $table = 'equipamentos';
    protected $primaryKey = 'idequipamento';

    protected $fillable = [
        'nome',
        'descricao',
        'quantidade',
        'catmat',
        'patrimonio',
        'status',
        'localizacao',
        'ativo'
    ];

    // O total muda por entrada, baixa e transferência, que já aparecem no histórico como movimentações
    protected array $historicoIgnorar = ['quantidade'];

    public function entradas()
    {
        return $this->hasMany(entrada_equipamento::class, 'idequipamento', 'idequipamento');
    }

    public function saidas()
    {
        return $this->hasMany(saida_equipamento::class, 'idequipamento', 'idequipamento');
    }

    public function transferencias()
    {
        return $this->hasMany(transferencia_equipamento::class, 'idequipamento', 'idequipamento');
    }

    // Onde estão as unidades deste equipamento (só locais com quantidade)
    public function locais()
    {
        return $this->hasMany(equipamento_local::class, 'idequipamento', 'idequipamento')
            ->where('quantidade', '>', 0);
    }
}
