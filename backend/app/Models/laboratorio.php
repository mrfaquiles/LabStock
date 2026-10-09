<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class laboratorio extends Model
{
    //
    protected $table = 'laboratorios';
    protected $primaryKey = 'idlaboratorio';
    protected $fillable = [
        'nome',
        'descricao',
    ];
    
    public function reagentes()
    {
        return $this->hasMany(entrada_reagente::class, 'idlaboratorio', 'idlaboratorio');
    }

    public function equipamentos()
    {
        return $this->hasMany(entrada_equipamento::class, 'idlaboratorio', 'idlaboratorio');
    }

    public function vidrarias()
    {
        return $this->hasMany(entrada_vidraria::class, 'idlaboratorio', 'idlaboratorio');
    }
}
