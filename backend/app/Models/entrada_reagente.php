<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class entrada_reagente extends Model
{
    //
    protected $table = 'entrada_reagentes';
    protected $primaryKey = 'identradareagente';
    protected $fillable = [
        'idreagente',
        'idlaboratorio',
        'idusuario',
    ];

    public function reagente()
    {
        return $this->belongsTo(reagente::class, 'idreagente', 'idreagente');
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
