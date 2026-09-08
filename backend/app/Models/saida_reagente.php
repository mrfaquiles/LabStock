<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class saida_reagente extends Model
{
    //
    protected $table = 'saida_reagentes';
    protected $primaryKey = 'idsaidareagente';
    protected $fillable = [
        'idreagente',
        'identrada',
        'idusuario',
        'quantidade',
        'data',
        'observacao',
    ];

    public function reagente()
    {
        return $this->belongsTo(reagente::class, 'idreagente', 'idreagente');
    }

    public function entrada()
    {
        return $this->belongsTo(entrada_reagente::class, 'identrada', 'identrada');
    }

    public function usuario()
    {
        return $this->belongsTo(usuario::class, 'idusuario', 'idusuario');
    }
}
