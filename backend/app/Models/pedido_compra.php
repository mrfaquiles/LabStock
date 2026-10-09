<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pedido de compra de um reagente ou vidraria, acompanhado até a entrega.
 */
class pedido_compra extends Model
{
    public const SOLICITADO = 'solicitado';
    public const EM_LICITACAO = 'em_licitacao';
    public const AGUARDANDO_ENTREGA = 'aguardando_entrega';
    public const RECEBIDO = 'recebido';
    public const CANCELADO = 'cancelado';

    // Pedidos que ainda vão chegar (contam como "em pedido" na previsão)
    public const EM_ANDAMENTO = [self::SOLICITADO, self::EM_LICITACAO, self::AGUARDANDO_ENTREGA];

    protected $table = 'pedidos_compra';
    protected $primaryKey = 'idpedido';
    protected $fillable = [
        'idreagente',
        'idvidraria',
        'idusuario',
        'quantidade',
        'status',
        'data_pedido',
        'previsao_entrega',
        'data_recebimento',
        'observacao',
    ];

    protected $appends = ['categoria'];

    public function reagente()
    {
        return $this->belongsTo(reagente::class, 'idreagente', 'idreagente');
    }

    public function vidraria()
    {
        return $this->belongsTo(vidraria::class, 'idvidraria', 'idvidraria');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'idusuario', 'id');
    }

    public function getCategoriaAttribute(): string
    {
        return $this->idreagente ? 'reagentes' : 'vidrarias';
    }

    public function scopeEmAndamento($query)
    {
        return $query->whereIn('status', self::EM_ANDAMENTO);
    }
}
