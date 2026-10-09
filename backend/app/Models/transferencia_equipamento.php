<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Transferência de equipamento entre laboratórios (inclusive de unidades diferentes).
 */
class transferencia_equipamento extends Model
{
    protected $table = 'transferencias_equipamento';
    protected $primaryKey = 'idtransferencia';
    protected $fillable = ['idequipamento', 'idorigem', 'iddestino', 'quantidade', 'idusuario', 'data', 'observacao'];

    public function equipamento()
    {
        return $this->belongsTo(equipamento::class, 'idequipamento', 'idequipamento');
    }

    public function origem()
    {
        return $this->belongsTo(laboratorio::class, 'idorigem', 'idlaboratorio');
    }

    public function destino()
    {
        return $this->belongsTo(laboratorio::class, 'iddestino', 'idlaboratorio');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idusuario', 'id');
    }
}
