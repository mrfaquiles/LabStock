<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Unidade (campus/sede) da instituição. Cada laboratório pertence a uma unidade.
 */
class unidade extends Model
{
    protected $table = 'unidades';
    protected $primaryKey = 'idunidade';
    protected $fillable = ['nome', 'endereco'];

    public function laboratorios()
    {
        return $this->hasMany(laboratorio::class, 'idunidade', 'idunidade');
    }
}
