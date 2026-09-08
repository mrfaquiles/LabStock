<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class saida_vidraria extends Model
{
    //
    protected $table = 'saida_vidrarias';
    protected $primaryKey = 'idsaidavidraria';
    protected $fillable = [
        'idvidraria',
        'idlaboratorio',
        'idusuario',
        'quantidade',
        'data',
        'observacao',
    ];

    public function vidraria()
    {
        return $this->belongsTo(vidraria::class, 'idvidraria', 'idvidraria');
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
