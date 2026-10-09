<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Quantidade de um equipamento em um laboratório.
 * idlaboratorio nulo = "sem local definido".
 */
class equipamento_local extends Model
{
    protected $table = 'equipamento_locais';
    protected $fillable = ['idequipamento', 'idlaboratorio', 'quantidade'];

    protected function casts(): array
    {
        return ['quantidade' => 'integer'];
    }

    public function laboratorio()
    {
        return $this->belongsTo(laboratorio::class, 'idlaboratorio', 'idlaboratorio');
    }

    /** Registro do equipamento naquele local (cria com zero se ainda não existir). */
    public static function doLocal(int $idequipamento, ?int $idlaboratorio): self
    {
        return static::where('idequipamento', $idequipamento)
            ->when($idlaboratorio, fn ($q) => $q->where('idlaboratorio', $idlaboratorio), fn ($q) => $q->whereNull('idlaboratorio'))
            ->lockForUpdate()
            ->first()
            ?? static::create(['idequipamento' => $idequipamento, 'idlaboratorio' => $idlaboratorio, 'quantidade' => 0]);
    }
}
