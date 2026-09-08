<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class entrada_equipamento extends Model
{
    //
    protected $table = 'entrada_equipamentos';
    protected $primaryKey = 'identradaequipamento';
    protected $fillable = [
        'idequipamento',
        'idlaboratorio',
        'idusuario',
        'quantidade',
        'data',
        'observacao',
    ];

    public function equipamento()
    {
        return $this->belongsTo(equipamento::class, 'idequipamento', 'idequipamento');
    }

    public function laboratorio()
    {
        return $this->belongsTo(laboratorio::class, 'idlaboratorio', 'idlaboratorio');
    }

    public function usuario()
    {
        return $this->belongsTo(usuario::class, 'idusuario', 'idusuario');
    }
}
