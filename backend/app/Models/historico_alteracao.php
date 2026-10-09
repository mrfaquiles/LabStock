<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro de alteração feita em um cadastro (ver trait Concerns\RegistraHistorico).
 */
class historico_alteracao extends Model
{
    protected $table = 'historico_alteracoes';
    public $timestamps = false;

    protected $fillable = ['entidade', 'registro_id', 'acao', 'alteracoes', 'idusuario', 'created_at'];

    protected function casts(): array
    {
        return [
            'alteracoes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idusuario', 'id');
    }
}
