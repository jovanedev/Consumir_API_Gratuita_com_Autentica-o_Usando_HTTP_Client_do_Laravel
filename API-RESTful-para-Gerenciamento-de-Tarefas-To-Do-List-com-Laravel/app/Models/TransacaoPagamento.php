<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransacaoPagamento extends Model
{
    
    use HasFactory;

    // Defina explicitamente o nome da tabela
    protected $table = 'transacao_pagamentos';

    protected $fillable = 
    [
        'loja_id',
        'cliente_id', 
        'metodo_pagamento', 
        'pedido_id', 
        'valor_total'
    ];

    public function cliente()
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function metodoPagamento()
    {
        return $this->belongsTo(FormaPagamento::class, 'metodo_pagamento');
    }

    public function pedido()
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    public function loja()
    {
        return $this->belongsTo(Loja::class, 'loja_id');
    }

}
